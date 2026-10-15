<?php

namespace Tests\Feature;

use App\Actions\Borrows\CreateBorrow;
use App\Actions\Borrows\ReceiveKeys;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class BorrowAuditTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): User
    {
        return User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->firstOrFail()->id,
        ]);
    }

    /** @return array{Borrow, User, Employee, array<int, int>} */
    private function borrowWithTwoKeys(): array
    {
        $creator = $this->operator();
        $operator = $this->operator();
        $employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $keyIds = Key::factory()->count(2)->create()->modelKeys();
        $borrow = app(CreateBorrow::class)->handle($creator, [
            'employee_id' => $employee->id, 'keys' => $keyIds, 'observation' => 'Original',
        ]);

        return [$borrow, $operator, $employee, $keyIds];
    }

    /** @param array<string, mixed> $properties */
    private function assertAudit(string $description, Model $subject, User $operator, array $properties): void
    {
        $activity = Activity::where('description', $description)
            ->where('subject_type', $subject::class)->where('subject_id', $subject->id)->sole();

        $this->assertSame(User::class, $activity->causer_type);
        $this->assertSame($operator->id, $activity->causer_id);
        foreach ($properties as $path => $value) {
            $this->assertTrue(Arr::has($activity->properties->all(), $path), "Missing audit property: $path");
            $this->assertSame($value, $activity->getProperty($path), $path);
        }
    }

    public function test_creation_records_key_ids_and_request_operator(): void
    {
        $operator = $this->operator();
        $employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $key = Key::factory()->create();

        $this->actingAs($operator)->post(route('borrows.store'), [
            'employee_id' => $employee->id, 'keys' => [$key->id],
        ])->assertSessionHasNoErrors()->assertSessionHas('flash.status', 'success');

        $borrow = Borrow::where('user_id', $operator->id)->sole();
        $this->assertDatabaseHas('borrow_key', ['borrow_id' => $borrow->id, 'key_id' => $key->id]);
        $this->assertAudit('keys_borrowed', $borrow, $operator, ['key_ids' => [$key->id]]);
    }

    public function test_edit_snapshots_preserve_returned_keys_and_identify_new_borrower(): void
    {
        [$borrow, $operator, $employee, $keyIds] = $this->borrowWithTwoKeys();
        app(ReceiveKeys::class)->handle($borrow, $borrow->user, [$keyIds[0]], 'Recepção');
        $newEmployee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $newKey = Key::factory()->create();

        $this->actingAs($operator)->put(route('borrows.update', $borrow), [
            'employee_id' => $newEmployee->id, 'keys' => [$keyIds[0], $newKey->id],
        ])->assertSessionHasNoErrors()->assertSessionHas('flash.status', 'success');

        $this->assertDatabaseHas('borrows', ['id' => $borrow->id, 'employee_id' => $newEmployee->id]);
        $this->assertDatabaseMissing('borrow_key', ['borrow_id' => $borrow->id, 'key_id' => $keyIds[1]]);
        $this->assertDatabaseHas('borrow_key', ['borrow_id' => $borrow->id, 'key_id' => $newKey->id]);
        $this->assertAudit('borrow_updated', $borrow, $operator, [
            'before.employee_id' => $employee->id, 'before.key_ids' => $keyIds,
            'before.pending_key_ids' => [$keyIds[1]], 'before.devolution' => null,
            'after.employee_id' => $newEmployee->id, 'after.key_ids' => [$keyIds[0], $newKey->id],
            'after.pending_key_ids' => [$newKey->id], 'after.devolution' => null,
        ]);
    }

    public function test_partial_return_logs_remaining_keys_without_closing(): void
    {
        [$borrow, $operator, , $keyIds] = $this->borrowWithTwoKeys();

        $this->actingAs($operator)->post(route('borrows.receive', $borrow), [
            'keys' => [$keyIds[0]], 'returned_by' => 'Recepção',
        ])->assertSessionHasNoErrors()->assertSessionHas('flash.status', 'success');

        $receipt = $borrow->received()->sole();
        $this->assertDatabaseHas('key_received', ['received_id' => $receipt->id, 'key_id' => $keyIds[0]]);
        $this->assertDatabaseHas('borrows', ['id' => $borrow->id, 'devolution' => null]);
        $this->assertAudit('keys_returned', $receipt, $operator, [
            'key_ids' => [$keyIds[0]],
            'before.pending_key_ids' => $keyIds, 'before.devolution' => null, 'before.received_by' => null,
            'after.pending_key_ids' => [$keyIds[1]], 'after.devolution' => null, 'after.received_by' => null,
        ]);
    }

    public function test_final_return_logs_closing_time_and_receiving_operator(): void
    {
        [$borrow, $operator, , $keyIds] = $this->borrowWithTwoKeys();
        app(ReceiveKeys::class)->handle($borrow, $borrow->user, [$keyIds[0]], 'Recepção');
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

        $this->actingAs($operator)->post(route('borrows.receive', $borrow), [
            'keys' => [$keyIds[1]], 'returned_by' => 'Recepção',
        ])->assertSessionHasNoErrors()->assertSessionHas('flash.status', 'success');

        $receipt = $borrow->received()->where('user_id', $operator->id)->sole();
        $this->assertDatabaseHas('borrows', [
            'id' => $borrow->id, 'devolution' => '2026-10-15 12:00:00', 'received_by' => $operator->id,
        ]);
        $this->assertAudit('keys_returned', $receipt, $operator, [
            'key_ids' => [$keyIds[1]],
            'before.pending_key_ids' => [$keyIds[1]], 'before.devolution' => null, 'before.received_by' => null,
            'after.pending_key_ids' => [], 'after.devolution' => '2026-10-15 12:00:00', 'after.received_by' => $operator->id,
        ]);
    }

    public function test_deleting_final_return_logs_reopening_and_deleted_receipt_id(): void
    {
        [$borrow, $operator, , $keyIds] = $this->borrowWithTwoKeys();
        $creator = $borrow->user;
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        app(ReceiveKeys::class)->handle($borrow, $creator, [$keyIds[0]], 'Recepção');
        $receipt = app(ReceiveKeys::class)->handle($borrow, $creator, [$keyIds[1]], 'Recepção');

        $this->actingAs($operator)->delete(route('borrows.receive.destroy', [$borrow, $receipt]))
            ->assertSessionHas('flash.status', 'success');

        $this->assertModelMissing($receipt);
        $this->assertDatabaseMissing('key_received', ['received_id' => $receipt->id]);
        $this->assertDatabaseHas('borrows', ['id' => $borrow->id, 'devolution' => null, 'received_by' => null]);
        $this->assertAudit('return_deleted', $borrow, $operator, [
            'received_id' => $receipt->id,
            'before.pending_key_ids' => [], 'before.devolution' => '2026-10-15 12:00:00', 'before.received_by' => $creator->id,
            'after.pending_key_ids' => [$keyIds[1]], 'after.devolution' => null, 'after.received_by' => null,
        ]);
    }

    public function test_deleting_borrow_keeps_snapshot_and_subject_id_after_cascade(): void
    {
        [$borrow, $operator, $employee, $keyIds] = $this->borrowWithTwoKeys();
        $receipt = app(ReceiveKeys::class)->handle($borrow, $borrow->user, [$keyIds[0]], 'Recepção');

        $this->actingAs($operator)->delete(route('borrows.destroy', $borrow))
            ->assertSessionHas('flash.status', 'success');

        $this->assertModelMissing($borrow);
        $this->assertModelMissing($receipt);
        $this->assertDatabaseMissing('borrow_key', ['borrow_id' => $borrow->id]);
        $this->assertDatabaseMissing('key_received', ['received_id' => $receipt->id]);
        $this->assertAudit('borrow_deleted', $borrow, $operator, [
            'before.employee_id' => $employee->id, 'before.key_ids' => $keyIds,
            'before.pending_key_ids' => [$keyIds[1]], 'before.devolution' => null, 'after' => null,
        ]);
    }

    public function test_observation_only_edit_keeps_v5_changes_and_request_operator(): void
    {
        [$borrow, $operator, $employee, $keyIds] = $this->borrowWithTwoKeys();

        $this->actingAs($operator)->put(route('borrows.update', $borrow), [
            'employee_id' => $employee->id, 'keys' => $keyIds, 'observation' => 'Corrigido',
        ])->assertSessionHasNoErrors()->assertSessionHas('flash.status', 'success');

        $this->assertDatabaseHas('borrows', ['id' => $borrow->id, 'observation' => 'Corrigido']);
        $activity = Activity::where('description', 'updated')->where('subject_type', Borrow::class)
            ->where('subject_id', $borrow->id)->sole();
        $this->assertSame($operator->id, $activity->causer_id);
        $this->assertSame('Original', $activity->attribute_changes->get('old')['observation']);
        $this->assertSame('Corrigido', $activity->attribute_changes->get('attributes')['observation']);
        $this->assertDatabaseMissing('activity_log', ['description' => 'borrow_updated', 'subject_id' => $borrow->id]);
    }
}
