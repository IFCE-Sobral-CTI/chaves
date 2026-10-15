<?php

namespace Tests\Feature;

use App\Actions\Borrows\CreateBorrow;
use App\Actions\Borrows\ReceiveKeys;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BorrowConcurrencyTest extends TestCase
{
    // Real commits are required: child processes cannot see RefreshDatabase fixtures.
    use DatabaseMigrations;

    private User $user;

    private Employee $employee;

    private Key $key;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if (! $app->environment('testing') || DB::connection()->getDatabaseName() !== 'testing' || DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Concurrency tests require the isolated MySQL testing database.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['permission_id' => Permission::where('description', 'Administrador')->firstOrFail()->id]);
        $this->employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $this->key = Key::factory()->create();
        $this->assertSame('REPEATABLE-READ', DB::selectOne('SELECT @@transaction_isolation AS isolation_level')->isolation_level);
    }

    private function borrow(): Borrow
    {
        return app(CreateBorrow::class)->handle($this->user, ['employee_id' => $this->employee->id, 'keys' => [$this->key->id]]);
    }

    private function waitFor(string $file, Process $process): void
    {
        $deadline = microtime(true) + 15;
        while (! is_file($file)) {
            if (! $process->isRunning() || microtime(true) > $deadline) {
                $this->fail('Worker failed to reach barrier: '.$process->getOutput().$process->getErrorOutput());
            }
            usleep(10000);
        }
    }

    private function race(array $holderInput, array $contenderInput): array
    {
        $barrier = sys_get_temp_dir().'/chaves-borrow-race-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $common = ['user_id' => $this->user->id, 'employee_id' => $this->employee->id, 'key_id' => $this->key->id, 'barrier' => $barrier];
        $worker = fn (array $input, string $role) => new Process([
            PHP_BINARY, base_path('tests/Fixtures/Borrows/concurrent-worker.php'),
            base64_encode(json_encode(array_merge($common, $input, ['role' => $role]), JSON_THROW_ON_ERROR)),
        ], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => 'testing', 'DB_CONNECTION' => 'mysql'], timeout: 25);
        $holder = $worker($holderInput, 'holder');
        $contender = $worker($contenderInput, 'contender');

        try {
            $holder->start();
            $this->waitFor($barrier.'/locked', $holder);
            $contender->start();
            $this->waitFor($barrier.'/attempting', $contender);
            $this->assertTrue($holder->isRunning());
            $this->assertTrue($contender->isRunning());
            touch($barrier.'/release');
            $holder->wait();
            $contender->wait();
            $this->assertSame(0, $holder->getExitCode(), $holder->getOutput().$holder->getErrorOutput());
            $this->assertSame(0, $contender->getExitCode(), $contender->getOutput().$contender->getErrorOutput());

            return [json_decode($holder->getOutput(), true, flags: JSON_THROW_ON_ERROR), json_decode($contender->getOutput(), true, flags: JSON_THROW_ON_ERROR)];
        } finally {
            $holder->stop();
            $contender->stop();
            foreach (glob($barrier.'/*') as $file) {
                unlink($file);
            }
            rmdir($barrier);
        }
    }

    public function test_two_creations_with_a_stale_snapshot_have_exactly_one_winner(): void
    {
        $before = Borrow::count();
        [$holder, $contender] = $this->race(['operation' => 'create'], ['operation' => 'create']);

        $this->assertSame('success', $holder['status']);
        $this->assertSame('conflict', $contender['status']);
        $this->assertArrayHasKey('keys', $contender['errors']);
        $this->assertSame($before + 1, Borrow::count());
        $this->assertSame(1, DB::table('borrow_key')->where('key_id', $this->key->id)->count());
    }

    public function test_key_deletion_waits_for_creation_and_preserves_committed_history(): void
    {
        $before = Borrow::count();
        [$holder, $contender] = $this->race(['operation' => 'create'], ['operation' => 'delete-key']);

        $this->assertSame('success', $holder['status']);
        $this->assertSame('conflict', $contender['status']);
        $this->assertSame($before + 1, Borrow::count());
        $this->assertDatabaseHas('keys', ['id' => $this->key->id]);
        $this->assertSame(1, DB::table('borrow_key')->where('key_id', $this->key->id)->count());
    }

    public function test_history_constraint_rollback_and_reapply_preserve_data(): void
    {
        $borrow = $this->borrow();
        $receipt = app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção');
        $assertRule = function (string $rule): void {
            $constraints = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
                ->whereIn('TABLE_NAME', ['borrow_key', 'key_received', 'borrows', 'receiveds'])
                ->whereIn('REFERENCED_TABLE_NAME', ['keys', 'employees', 'users'])->get();
            $this->assertCount(6, $constraints);
            $this->assertEquals(
                $rule === 'RESTRICT' ? ['RESTRICT' => 6] : ['CASCADE' => 5, 'SET NULL' => 1],
                $constraints->countBy('DELETE_RULE')->all(),
            );
            $this->assertSame(['CASCADE'], $constraints->pluck('UPDATE_RULE')->unique()->values()->all());
        };
        $migration = require database_path('migrations/2026_10_15_000000_protect_borrow_history_from_cascading_deletes.php');
        $assertRule('RESTRICT');

        $migration->down();
        try {
            $assertRule('CASCADE');
        } finally {
            $migration->up();
        }

        $assertRule('RESTRICT');
        $this->assertDatabaseHas('borrows', ['id' => $borrow->id]);
        $this->assertDatabaseHas('receiveds', ['id' => $receipt->id]);
        $this->assertDatabaseHas('borrow_key', ['borrow_id' => $borrow->id, 'key_id' => $this->key->id]);
        $this->assertDatabaseHas('key_received', ['received_id' => $receipt->id, 'key_id' => $this->key->id]);
    }

    public function test_simultaneous_returns_create_only_one_receipt(): void
    {
        $borrow = $this->borrow();
        $input = ['operation' => 'receive', 'borrow_id' => $borrow->id];
        [$holder, $contender] = $this->race($input, $input);

        $this->assertSame('success', $holder['status']);
        $this->assertSame('conflict', $contender['status']);
        $this->assertSame(1, $borrow->received()->count());
        $this->assertNotNull($borrow->fresh()->devolution);
    }

    public function test_creation_after_waiting_for_return_sees_current_availability(): void
    {
        $borrow = $this->borrow();
        [$holder, $contender] = $this->race(['operation' => 'receive', 'borrow_id' => $borrow->id], ['operation' => 'create']);

        $this->assertSame('success', $holder['status']);
        $this->assertSame('success', $contender['status']);
        $this->assertNotNull($borrow->fresh()->devolution);
        $this->assertSame(1, DB::table('borrow_key')->join('borrows', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->where('borrow_key.key_id', $this->key->id)->whereNull('borrows.devolution')->count());
    }

    public function test_creation_after_partial_return_sees_current_receipt(): void
    {
        $borrow = $this->borrow();
        $otherKey = Key::factory()->create();
        $borrow->keys()->attach($otherKey->id);

        [$holder, $contender] = $this->race(['operation' => 'receive', 'borrow_id' => $borrow->id], ['operation' => 'create']);

        $this->assertSame('success', $holder['status']);
        $this->assertSame('success', $contender['status']);
        $this->assertNull($borrow->fresh()->devolution);
        $this->assertSame([$this->key->id], $borrow->receivedKeys());
        $this->assertEqualsCanonicalizing([$this->key->id, $otherKey->id], Borrow::pendingKeyIds()->pluck('key_id')->all());
    }

    public function test_reopening_and_lending_do_not_create_two_occupations(): void
    {
        $borrow = $this->borrow();
        $receipt = app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção');
        [$holder, $contender] = $this->race([
            'operation' => 'delete-return', 'borrow_id' => $borrow->id, 'received_id' => $receipt->id,
        ], ['operation' => 'create']);

        $this->assertSame('success', $holder['status']);
        $this->assertSame('conflict', $contender['status']);
        $this->assertDatabaseMissing('receiveds', ['id' => $receipt->id]);
        $this->assertNull($borrow->fresh()->devolution);
        $this->assertSame(1, DB::table('borrow_key')->where('key_id', $this->key->id)->count());
    }
}
