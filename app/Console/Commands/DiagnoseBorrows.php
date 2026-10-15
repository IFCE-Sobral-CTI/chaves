<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class DiagnoseBorrows extends Command
{
    protected $signature = 'borrows:diagnose';

    protected $description = 'Lista inconsistências de empréstimos por IDs em JSON Lines, sem alterar dados';

    public function handle(): int
    {
        $returns = DB::table('receiveds')
            ->join('key_received', 'key_received.received_id', '=', 'receiveds.id')
            ->select('receiveds.id as received_id', 'receiveds.borrow_id', 'key_received.key_id');
        // Include closed borrows here so premature closure is also diagnosed.
        $pending = DB::table('borrow_key')->whereNotExists((clone $returns)
            ->whereColumn('receiveds.borrow_id', 'borrow_key.borrow_id')
            ->whereColumn('key_received.key_id', 'borrow_key.key_id'))
            ->select('borrow_key.borrow_id', 'borrow_key.key_id')->distinct();
        $activePending = (clone $pending)->join('borrows', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->whereNull('borrows.devolution');
        $occupiedTwice = DB::query()->fromSub($activePending, 'pending')
            ->select('key_id')->groupBy('key_id')->havingRaw('COUNT(*) > 1');
        $returnedTwice = (clone $returns)->select('receiveds.borrow_id', 'key_received.key_id')
            ->groupBy('receiveds.borrow_id', 'key_received.key_id')->havingRaw('COUNT(*) > 1');
        $pendingForBorrow = (clone $pending)->whereColumn('borrow_key.borrow_id', 'borrows.id');

        $queries = [
            'duplicate_borrow_keys' => DB::table('borrow_key as pivot')->whereExists(fn ($query) => $query
                ->selectRaw('1')->from('borrow_key as duplicate')
                ->whereColumn('duplicate.borrow_id', 'pivot.borrow_id')
                ->whereColumn('duplicate.key_id', 'pivot.key_id')
                ->whereColumn('duplicate.id', '!=', 'pivot.id'))
                ->select('pivot.id as pivot_id', 'pivot.borrow_id', 'pivot.key_id')->orderBy('pivot.id'),
            'multiple_pending_borrows' => (clone $activePending)->whereIn('borrow_key.key_id', $occupiedTwice)
                ->orderBy('borrow_key.key_id')->orderBy('borrow_key.borrow_id'),
            'duplicate_returns' => (clone $returns)->joinSub($returnedTwice, 'duplicates', function (JoinClause $join) {
                $join->on('duplicates.borrow_id', '=', 'receiveds.borrow_id')
                    ->on('duplicates.key_id', '=', 'key_received.key_id');
            })->addSelect('key_received.id as pivot_id')->orderBy('key_received.id'),
            'empty_receipts' => DB::table('receiveds')->whereNotExists(fn ($query) => $query
                ->selectRaw('1')->from('key_received')->whereColumn('received_id', 'receiveds.id'))
                ->select('id as received_id', 'borrow_id')->orderBy('id'),
            'empty_borrows' => DB::table('borrows')->whereNotExists(fn ($query) => $query
                ->selectRaw('1')->from('borrow_key')->whereColumn('borrow_id', 'borrows.id'))
                ->select('id as borrow_id')->orderBy('id'),
            'foreign_returned_keys' => (clone $returns)->whereNotExists(fn ($query) => $query
                ->selectRaw('1')->from('borrow_key')
                ->whereColumn('borrow_key.borrow_id', 'receiveds.borrow_id')
                ->whereColumn('borrow_key.key_id', 'key_received.key_id'))
                ->addSelect('key_received.id as pivot_id')->orderBy('key_received.id'),
            'closure_mismatches' => DB::table('borrows')
                ->where(fn ($query) => $query->whereNull('devolution')->whereNotExists($pendingForBorrow))
                ->orWhere(fn ($query) => $query->whereNotNull('devolution')->whereExists($pendingForBorrow))
                ->select('id as borrow_id')->selectRaw('devolution IS NOT NULL AS closed')->orderBy('id'),
        ];

        DB::transaction(function () use ($queries) {
            $counts = [];
            foreach ($queries as $issue => $query) {
                $counts[$issue] = 0;
                foreach ($query->cursor() as $row) {
                    $this->line(json_encode(['issue' => $issue, ...(array) $row], JSON_THROW_ON_ERROR));
                    $counts[$issue]++;
                }
            }
            $this->line(json_encode(['summary' => $counts], JSON_THROW_ON_ERROR));
        });

        return self::SUCCESS;
    }
}
