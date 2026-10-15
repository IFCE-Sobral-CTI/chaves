<?php

namespace Tests\Feature;

use App\Actions\Borrows\CreateBorrow;
use App\Actions\Borrows\DeleteBorrow;
use App\Actions\Borrows\ReceiveKeys;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Received;
use App\Models\Room;
use App\Models\Rule;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BorrowHistoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private Employee $employee;

    private Key $key;

    protected function setUp(): void
    {
        parent::setUp();
        (new AuthServiceProvider($this->app))->boot(new Rule);
        $this->admin = User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->firstOrFail()->id,
        ]);
        $this->owner = User::factory()->create(['permission_id' => Permission::factory()->create()->id]);
        $this->employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $this->key = Key::factory()->create();
    }

    private function parents(): array
    {
        return [
            'keys' => $this->key,
            'rooms' => $this->key->room,
            'blocks' => $this->key->room->block,
            'employees' => $this->employee,
            'users' => $this->owner,
            'permissions' => $this->owner->permission,
        ];
    }

    public function test_parent_deletions_preserve_open_and_closed_borrow_history(): void
    {
        $borrow = app(CreateBorrow::class)->handle($this->owner, [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id],
        ]);

        foreach ([false, true] as $closed) {
            if ($closed) {
                app(ReceiveKeys::class)->handle($borrow, $this->owner, [$this->key->id], 'Recepção');
            }
            $logs = DB::table('activity_log')->count();
            foreach ($this->parents() as $resource => $model) {
                $this->actingAs($this->admin)->delete(route($resource.'.destroy', $model))
                    ->assertRedirect(route($resource.'.index'))
                    ->assertSessionHas('flash.status', 'danger')
                    ->assertSessionHas('flash.message', 'Não é possível excluir este registro porque ele possui histórico de empréstimos ou devoluções.');

                $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
                $this->assertDatabaseHas('borrows', ['id' => $borrow->id]);
                $this->assertDatabaseHas('borrow_key', ['borrow_id' => $borrow->id, 'key_id' => $this->key->id]);
                $this->assertSame($closed, $borrow->fresh()->devolution !== null);
                $this->assertSame($closed ? 1 : 0, $borrow->received()->count());
                $this->assertSame($logs, DB::table('activity_log')->count());
            }
        }
    }

    public function test_direct_sql_cannot_delete_a_receipt_user_or_a_legacy_foreign_key(): void
    {
        $borrow = app(CreateBorrow::class)->handle($this->owner, [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id],
        ]);
        $legacyKey = Key::factory()->create();
        $receipt = Received::create([
            'borrow_id' => $borrow->id, 'user_id' => $this->admin->id, 'receiver' => 'Recepção',
        ]);
        $receipt->keys()->attach($legacyKey->id);
        $finalReceiver = User::factory()->create(['permission_id' => $this->owner->permission_id]);
        $borrow->update(['received_by' => $finalReceiver->id]);

        foreach ([['keys', $legacyKey->id], ['users', $this->admin->id], ['users', $finalReceiver->id]] as [$table, $id]) {
            try {
                DB::table($table)->where('id', $id)->delete();
                $this->fail('A direct deletion bypassed history protection.');
            } catch (QueryException $exception) {
                $this->assertSame(1451, (int) $exception->errorInfo[1]);
            }
            $this->assertDatabaseHas($table, ['id' => $id]);
            $this->assertDatabaseHas('receiveds', ['id' => $receipt->id]);
            $this->assertDatabaseHas('key_received', ['received_id' => $receipt->id, 'key_id' => $legacyKey->id]);
            $this->assertSame($finalReceiver->id, $borrow->fresh()->received_by);
        }
    }

    public function test_unreferenced_parents_can_still_be_deleted(): void
    {
        foreach ($this->parents() as $resource => $model) {
            $this->actingAs($this->admin)->delete(route($resource.'.destroy', $model))
                ->assertRedirect(route($resource.'.index'))->assertSessionHas('flash.status', 'success');
            $this->assertDatabaseMissing($model->getTable(), ['id' => $model->id]);
        }
    }

    public function test_unused_descendants_can_still_be_cascade_deleted(): void
    {
        $room = $this->key->room;
        $block = $room->block;
        $otherRoom = Room::factory()->create(['block_id' => $block->id]);
        $otherKey = Key::factory()->create(['room_id' => $otherRoom->id]);

        $this->actingAs($this->admin)->delete(route('rooms.destroy', $room))
            ->assertSessionHas('flash.status', 'success');
        $this->assertDatabaseMissing('keys', ['id' => $this->key->id]);

        $this->delete(route('blocks.destroy', $block))->assertSessionHas('flash.status', 'success');
        $this->assertDatabaseMissing('rooms', ['id' => $otherRoom->id]);
        $this->assertDatabaseMissing('keys', ['id' => $otherKey->id]);

        $this->delete(route('permissions.destroy', $this->owner->permission))
            ->assertSessionHas('flash.status', 'success');
        $this->assertDatabaseMissing('users', ['id' => $this->owner->id]);
    }

    public function test_explicit_borrow_deletion_still_cascades_receipts_and_pivots(): void
    {
        $borrow = app(CreateBorrow::class)->handle($this->owner, [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id],
        ]);
        $receipt = app(ReceiveKeys::class)->handle($borrow, $this->owner, [$this->key->id], 'Recepção');

        $this->actingAs($this->admin);
        app(DeleteBorrow::class)->handle($borrow);

        $this->assertDatabaseMissing('borrows', ['id' => $borrow->id]);
        $this->assertDatabaseMissing('receiveds', ['id' => $receipt->id]);
        $this->assertDatabaseMissing('borrow_key', ['borrow_id' => $borrow->id]);
        $this->assertDatabaseMissing('key_received', ['received_id' => $receipt->id]);
        foreach ($this->parents() as $model) {
            $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
        }
    }
}
