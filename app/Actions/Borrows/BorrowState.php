<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowState
{
    public function keyIds(Borrow $borrow): array
    {
        return DB::table('borrow_key')->where('borrow_id', $borrow->id)
            ->lockForUpdate()->pluck('key_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function receivedIds(Borrow $borrow): array
    {
        return DB::table('key_received')->join('receiveds', 'receiveds.id', '=', 'key_received.received_id')
            ->where('receiveds.borrow_id', $borrow->id)->lockForUpdate()
            ->pluck('key_received.key_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function pendingIds(Borrow $borrow): array
    {
        return array_values(array_diff($this->keyIds($borrow), $this->receivedIds($borrow)));
    }

    /**
     * Locking reads see committed changes after waiting, even in REPEATABLE READ.
     * Avoid a non-locking subquery whose snapshot could precede the key lock.
     */
    public function ensureAvailable(array $keyIds, ?int $exceptBorrowId = null): void
    {
        if ($keyIds === []) {
            return;
        }

        $occupations = DB::table('borrow_key')
            ->join('borrows', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->whereNull('borrows.devolution')->whereIn('borrow_key.key_id', $keyIds)
            ->when($exceptBorrowId !== null, fn ($query) => $query->where('borrows.id', '!=', $exceptBorrowId))
            ->select('borrow_key.borrow_id', 'borrow_key.key_id')->lockForUpdate()->get();

        if ($occupations->isEmpty()) {
            return;
        }

        $returns = DB::table('key_received')
            ->join('receiveds', 'receiveds.id', '=', 'key_received.received_id')
            ->whereIn('receiveds.borrow_id', $occupations->pluck('borrow_id')->unique())
            ->whereIn('key_received.key_id', $keyIds)
            ->select('receiveds.borrow_id', 'key_received.key_id')->lockForUpdate()->get();
        $returnedPairs = $returns->mapWithKeys(fn ($row) => [$row->borrow_id.':'.$row->key_id => true]);

        foreach ($occupations as $occupation) {
            if (! isset($returnedPairs[$occupation->borrow_id.':'.$occupation->key_id])) {
                throw ValidationException::withMessages(['keys' => 'Alguma das chaves selecionadas já está emprestada e ainda não foi devolvida.']);
            }
        }
    }
}
