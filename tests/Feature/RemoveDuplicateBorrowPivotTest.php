<?php

namespace Tests\Feature;

use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Received;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RemoveDuplicateBorrowPivotTest extends TestCase
{
    use RefreshDatabase;

    public static function pivotTables(): array
    {
        return ['borrow keys' => ['borrow_key'], 'returned keys' => ['key_received']];
    }

    /** @return array{Borrow, array<string, mixed>, int} */
    private function duplicate(string $table): array
    {
        $user = User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->firstOrFail()->id,
        ]);
        $key = Key::factory()->create();
        $borrow = Borrow::factory()->create([
            'employee_id' => Employee::factory()->create(['type' => Employee::EMPLOYEE])->id,
            'user_id' => $user->id, 'devolution' => null,
        ]);
        $borrow->keys()->attach($key);
        $row = ['borrow_id' => $borrow->id, 'key_id' => $key->id];
        if ($table === 'key_received') {
            $receipt = Received::create(['borrow_id' => $borrow->id, 'user_id' => $user->id, 'receiver' => 'Recepção']);
            $receipt->keys()->attach($key);
            $row = ['received_id' => $receipt->id, 'key_id' => $key->id];
        }
        $pivotId = DB::table($table)->insertGetId($row);

        return [$borrow, [
            'borrow' => $borrow->id, 'table' => $table, 'pivot' => $pivotId,
            '--user' => $user->id, '--reason' => 'Vínculo redundante conferido pelo operador',
        ], $key->id];
    }

    #[DataProvider('pivotTables')]
    public function test_simulation_does_not_change_pivots_or_audit(string $table): void
    {
        [$borrow, $arguments] = $this->duplicate($table);
        $count = DB::table($table)->count();
        $logs = Activity::count();
        $before = $borrow->fresh()->getAttributes();
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->assertSame(0, Artisan::call('borrows:remove-duplicate', $arguments));

        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('simulated', $result['status']);
        $this->assertSame($arguments['pivot'], $result['before']['id']);
        $this->assertSame($count, DB::table($table)->count());
        $this->assertSame($logs, Activity::count());
        $this->assertSame($before, $borrow->fresh()->getAttributes());
        foreach ($queries as $sql) {
            $this->assertMatchesRegularExpression('/^(select|savepoint|release savepoint)\b/i', $sql);
        }
    }

    #[DataProvider('pivotTables')]
    public function test_applies_only_selected_duplicate_and_reexecution_is_a_noop(string $table): void
    {
        [$borrow, $arguments, $keyId] = $this->duplicate($table);
        $count = DB::table($table)->count();
        $pending = Borrow::whereKey($borrow->id)->keysNotReceived();
        $received = $borrow->receivedKeys();

        $this->assertSame(0, Artisan::call('borrows:remove-duplicate', [...$arguments, '--apply' => true]));

        $this->assertSame('removed', json_decode(Artisan::output(), true)['status']);
        $this->assertDatabaseMissing($table, ['id' => $arguments['pivot']]);
        $this->assertSame($count - 1, DB::table($table)->count());
        $this->assertSame([$keyId], $borrow->keys()->pluck('keys.id')->all());
        $this->assertSame($pending, Borrow::whereKey($borrow->id)->keysNotReceived());
        $this->assertSame($received, $borrow->receivedKeys());
        $activity = Activity::where('description', 'borrow_duplicate_removed')->sole();
        $this->assertSame(Borrow::class, $activity->subject_type);
        $this->assertSame($borrow->id, $activity->subject_id);
        $this->assertSame(User::class, $activity->causer_type);
        $this->assertSame($arguments['--user'], $activity->causer_id);
        $this->assertSame($table, $activity->getProperty('table'));
        $this->assertSame($arguments['--reason'], $activity->getProperty('reason'));
        $this->assertSame($arguments['pivot'], $activity->getProperty('before.id'));
        $this->assertSame($keyId, $activity->getProperty('before.key_id'));
        $this->assertTrue($activity->properties->has('after'));
        $this->assertNull($activity->getProperty('after'));
        $logs = Activity::count();

        $this->assertSame(0, Artisan::call('borrows:remove-duplicate', [...$arguments, '--apply' => true]));

        $this->assertSame('already_absent', json_decode(Artisan::output(), true)['status']);
        $this->assertSame($count - 1, DB::table($table)->count());
        $this->assertSame($logs, Activity::count());

        $remainingId = DB::table($table)->where('key_id', $keyId)->value('id');
        $this->artisan('borrows:remove-duplicate', [...$arguments, 'pivot' => $remainingId, '--apply' => true])
            ->expectsOutputToContain('Não existe outro vínculo do mesmo par')->assertExitCode(1);
        $this->assertDatabaseHas($table, ['id' => $remainingId]);
        $this->assertSame($logs, Activity::count());
    }

    public function test_rejects_invalid_input_missing_reason_and_non_admin_without_writes(): void
    {
        [$borrow, $arguments] = $this->duplicate('borrow_key');
        $viewer = User::factory()->create([
            'permission_id' => Permission::where('description', 'Visualizador')->firstOrFail()->id,
        ]);
        $count = DB::table('borrow_key')->count();
        $logs = Activity::count();

        foreach ([['table' => 'users'], ['pivot' => 'invalid'], ['borrow' => 0], ['--reason' => '   '], ['--user' => null], ['--user' => $viewer->id]] as $invalid) {
            $this->assertSame(1, Artisan::call('borrows:remove-duplicate', array_replace($arguments, ['--apply' => true], $invalid)));
        }

        $this->assertSame($count, DB::table('borrow_key')->count());
        $this->assertSame($logs, Activity::count());
        $this->assertDatabaseHas('borrow_key', ['id' => $arguments['pivot'], 'borrow_id' => $borrow->id]);
    }

    #[DataProvider('pivotTables')]
    public function test_refuses_pivot_from_another_borrow(string $table): void
    {
        [, $arguments] = $this->duplicate($table);
        [$other] = $this->duplicate($table);
        $logs = Activity::count();

        $this->artisan('borrows:remove-duplicate', [...$arguments, 'borrow' => $other->id, '--apply' => true])
            ->expectsOutputToContain('O vínculo não pertence')->assertExitCode(1);

        $this->assertDatabaseHas($table, ['id' => $arguments['pivot']]);
        $this->assertSame($logs, Activity::count());
    }

    public function test_returns_in_different_receipts_are_not_interchangeable_duplicates(): void
    {
        [$borrow, $arguments, $keyId] = $this->duplicate('key_received');
        DB::table('key_received')->where('id', $arguments['pivot'])->delete();
        $receipt = Received::create(['borrow_id' => $borrow->id, 'user_id' => $borrow->user_id, 'receiver' => 'Recepção']);
        $receipt->keys()->attach($keyId);
        $arguments['pivot'] = DB::table('key_received')->where('received_id', $receipt->id)->value('id');
        $logs = Activity::count();

        $this->artisan('borrows:remove-duplicate', [...$arguments, '--apply' => true])
            ->expectsOutputToContain('Não existe outro vínculo do mesmo par')->assertExitCode(1);

        $this->assertDatabaseHas('key_received', ['id' => $arguments['pivot']]);
        $this->assertSame($logs, Activity::count());
    }

    public function test_refuses_duplicate_returns_of_a_key_outside_the_borrow(): void
    {
        [, $arguments] = $this->duplicate('key_received');
        $receiptId = DB::table('key_received')->where('id', $arguments['pivot'])->value('received_id');
        $foreignKey = Key::factory()->create();
        DB::table('key_received')->where('received_id', $receiptId)->update(['key_id' => $foreignKey->id]);
        $logs = Activity::count();

        $this->artisan('borrows:remove-duplicate', [...$arguments, '--apply' => true])
            ->expectsOutputToContain('A chave do vínculo não pertence')->assertExitCode(1);

        $this->assertDatabaseHas('key_received', ['id' => $arguments['pivot'], 'key_id' => $foreignKey->id]);
        $this->assertSame($logs, Activity::count());
    }

    public function test_failure_in_audit_rolls_back_the_deleted_pivot(): void
    {
        [, $arguments] = $this->duplicate('borrow_key');
        $logs = Activity::count();
        DB::listen(function (QueryExecuted $query): void {
            if (str_contains($query->sql, 'insert into `activity_log`')) {
                throw new RuntimeException('Injected audit failure');
            }
        });

        try {
            Artisan::call('borrows:remove-duplicate', [...$arguments, '--apply' => true]);
            $this->fail('The audit failure was not triggered.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected audit failure', $exception->getMessage());
        }

        $this->assertDatabaseHas('borrow_key', ['id' => $arguments['pivot']]);
        $this->assertSame($logs, Activity::count());
    }
}
