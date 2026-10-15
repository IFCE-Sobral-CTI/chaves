<?php

namespace Tests\Feature;

use App\Models\Borrow;
use App\Models\Key;
use App\Models\Received;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BorrowDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('borrows')->delete();
    }

    private function borrow(array $keyIds, bool $closed = false): Borrow
    {
        $borrow = Borrow::factory()->create([
            'devolution' => $closed ? now() : null,
            'user_id' => User::firstOrFail()->id,
        ]);
        $borrow->keys()->attach($keyIds);

        return $borrow;
    }

    private function receipt(Borrow $borrow, array $keyIds): Received
    {
        $receipt = Received::create([
            'borrow_id' => $borrow->id, 'user_id' => $borrow->user_id, 'receiver' => 'Recepção',
        ]);
        $receipt->keys()->attach($keyIds);

        return $receipt;
    }

    private function diagnose(): array
    {
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries) {
            $queries[] = $query->sql;
        });
        $this->assertSame(0, Artisan::call('borrows:diagnose'));
        $this->assertNotEmpty($queries);
        foreach ($queries as $sql) {
            $this->assertMatchesRegularExpression('/^(select|savepoint|release savepoint)\b/i', $sql);
            $this->assertStringNotContainsString('for update', strtolower($sql));
        }

        return array_map(fn ($line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR),
            explode("\n", trim(Artisan::output())));
    }

    public function test_consistent_partial_returns_and_reloans_produce_zero_issues(): void
    {
        [$x, $y, $z] = Key::factory()->count(3)->create()->all();
        $a = $this->borrow([$x->id, $y->id]);
        $this->receipt($a, [$x->id]);
        $b = $this->borrow([$x->id], closed: true);
        $this->receipt($b, [$x->id]);
        $this->borrow([$x->id, $z->id]);

        $report = $this->diagnose();

        $this->assertCount(1, $report);
        $this->assertCount(6, $report[0]['summary']);
        $this->assertSame([0], array_values(array_unique($report[0]['summary'])));
    }

    public function test_reports_all_six_issues_with_ids_and_does_not_change_data(): void
    {
        [$x, $y, $z, $foreign] = Key::factory()->count(4)->create()->all();
        $a = $this->borrow([$x->id]);
        $b = $this->borrow([$x->id]);
        $prematurelyClosed = $this->borrow([$y->id], closed: true);
        $emptyReceipt = $this->receipt($a, []);
        $emptyBorrow = $this->borrow([], closed: true);
        $fullyReturned = $this->borrow([$z->id]);
        $first = $this->receipt($fullyReturned, [$z->id]);
        $this->receipt($fullyReturned, [$z->id]);
        $first->keys()->attach($z->id);
        $foreignReceipt = $this->receipt($b, [$foreign->id]);
        $tables = ['borrows', 'borrow_key', 'receiveds', 'key_received', 'activity_log'];
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);

        $report = collect($this->diagnose());

        $this->assertSame([
            'multiple_pending_borrows' => 2,
            'duplicate_returns' => 3,
            'empty_receipts' => 1,
            'empty_borrows' => 1,
            'foreign_returned_keys' => 1,
            'closure_mismatches' => 2,
        ], $report->last()['summary']);
        $issues = $report->filter(fn ($row) => isset($row['issue']))->groupBy('issue');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $issues['multiple_pending_borrows']->pluck('borrow_id')->all());
        $this->assertSame([$x->id], $issues['multiple_pending_borrows']->pluck('key_id')->unique()->values()->all());
        $this->assertSame([$fullyReturned->id], $issues['duplicate_returns']->pluck('borrow_id')->unique()->values()->all());
        $this->assertSame([$emptyReceipt->id], $issues['empty_receipts']->pluck('received_id')->all());
        $this->assertSame([$emptyBorrow->id], $issues['empty_borrows']->pluck('borrow_id')->all());
        $this->assertSame([$foreignReceipt->id], $issues['foreign_returned_keys']->pluck('received_id')->all());
        $this->assertEqualsCanonicalizing([$prematurelyClosed->id, $fullyReturned->id], $issues['closure_mismatches']->pluck('borrow_id')->all());
        foreach ($issues->flatten(1) as $row) {
            $this->assertEmpty(array_diff(array_keys($row), ['issue', 'borrow_id', 'key_id', 'received_id', 'pivot_id', 'closed']));
        }
        foreach ($before as $table => $count) {
            $this->assertSame($count, DB::table($table)->count());
        }
    }
}
