<?php

namespace Tests\Feature;

use App\Actions\Borrows\CreateBorrow;
use App\Actions\Borrows\ReceiveKeys;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoveLegacyBorrowReceivedTest extends TestCase
{
    use DatabaseMigrations;

    public function test_removal_preserves_partial_returns_and_rollback_only_restores_column_default(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $keyIds = Key::factory()->count(2)->create()->modelKeys();
        $borrow = app(CreateBorrow::class)->handle($user, ['employee_id' => $employee->id, 'keys' => $keyIds]);
        $receipt = app(ReceiveKeys::class)->handle($borrow, $user, [$keyIds[0]], 'Recepção');
        $migration = require database_path('migrations/2026_10_15_135738_drop_received_from_borrow_key_table.php');
        $migration->down();
        DB::table('borrow_key')->where('borrow_id', $borrow->id)->update(['received' => true]);

        try {
            $migration->up();

            $this->assertFalse(Schema::hasColumn('borrow_key', 'received'));
            $this->assertSame($keyIds, $borrow->keys()->orderBy('keys.id')->pluck('keys.id')->all());
            $this->assertSame([$keyIds[0]], $borrow->receivedKeys());
            $this->assertSame([$keyIds[1]], Borrow::whereKey($borrow->id)->keysNotReceived());
            $this->assertModelExists($receipt);
            $this->assertDatabaseHas('key_received', ['received_id' => $receipt->id, 'key_id' => $keyIds[0]]);
            $this->assertDatabaseHas('borrows', ['id' => $borrow->id, 'devolution' => null]);

            $migration->down();

            $this->assertTrue(Schema::hasColumn('borrow_key', 'received'));
            $this->assertSame(0, DB::table('borrow_key')->where('borrow_id', $borrow->id)->where('received', true)->count());
            $this->assertSame([$keyIds[0]], $borrow->receivedKeys());
        } finally {
            if (Schema::hasColumn('borrow_key', 'received')) {
                $migration->up();
            }
        }
    }
}
