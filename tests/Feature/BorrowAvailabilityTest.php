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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BorrowAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->firstOrFail()->id,
        ]);
    }

    private function openBorrow(User $user, Employee $employee, array $keys): Borrow
    {
        $borrow = Borrow::factory()->create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'devolution' => null,
        ]);
        $borrow->keys()->sync($keys);

        return $borrow;
    }

    private function received(Borrow $borrow, array $keys): Received
    {
        $received = Received::create([
            'borrow_id' => $borrow->id,
            'user_id' => $borrow->user_id,
            'receiver' => 'Recepção',
        ]);
        $received->keys()->sync($keys);

        return $received;
    }

    public function test_partial_return_does_not_release_key_pending_in_another_borrow(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        [$x, $y] = Key::factory()->count(2)->create()->all();
        $a = $this->openBorrow($user, $employee, [$x->id, $y->id]);
        $this->received($a, [$x->id]);

        $this->assertNotContains($x->id, Borrow::keysNotReceived());
        $this->assertContains($y->id, Borrow::keysNotReceived());

        $b = $this->openBorrow($user, $employee, [$x->id]);
        $this->assertContains($x->id, Borrow::keysNotReceived());
        $this->assertTrue(Key::borrowed($a->id)->whereKey($x->id)->exists());
        $this->assertFalse(Key::borrowed($b->id)->whereKey($x->id)->exists());

        $this->actingAs($user)->post(route('borrows.store'), [
            'employee_id' => $employee->id,
            'keys' => [$x->id],
        ])->assertSessionHasErrors('keys');
    }

    public function test_received_ids_are_a_list_and_use_one_query_with_duplicate_history(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create();
        [$x, $y] = Key::factory()->count(2)->create()->all();
        $borrow = $this->openBorrow($user, $employee, [$x->id, $y->id]);
        $this->received($borrow, [$x->id]);
        $this->received($borrow, [$x->id, $y->id]);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $ids = $borrow->receivedKeys();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries);
        $this->assertTrue(array_is_list($ids));
        $this->assertEqualsCanonicalizing([$x->id, $y->id], $ids);
    }

    public function test_invalid_key_lists_do_not_create_borrows(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $key = Key::factory()->create();
        $before = Borrow::count();

        foreach ([[], [$key->id, $key->id], [999999999], ['invalid']] as $keys) {
            $this->actingAs($user)->post(route('borrows.store'), [
                'employee_id' => $employee->id,
                'keys' => $keys,
            ])->assertSessionHasErrors();
        }

        $this->assertSame($before, Borrow::count());
    }

    public function test_restricted_borrowers_need_validity_and_permission_for_every_key(): void
    {
        $user = $this->admin();
        [$x, $y] = Key::factory()->count(2)->create()->all();
        $before = Borrow::count();

        foreach ([Employee::STUDENT, Employee::EXTERNAL] as $type) {
            $employee = Employee::factory()->create(['type' => $type, 'valid_until' => null]);
            $employee->borrowable_keys()->sync([$x->id]);
            foreach ([null, now()->subDay()] as $validUntil) {
                $employee->update(['valid_until' => $validUntil]);
                $this->actingAs($user)->post(route('borrows.store'), [
                    'employee_id' => $employee->id, 'keys' => [$x->id],
                ])->assertSessionHasErrors('employee_id');
            }

            $employee->update(['valid_until' => now()->addDay()]);
            $this->actingAs($user)->post(route('borrows.store'), [
                'employee_id' => $employee->id, 'keys' => [$x->id, $y->id],
            ])->assertSessionHasErrors('keys');
        }

        $this->assertSame($before, Borrow::count());
    }

    public function test_valid_student_can_borrow_an_authorized_key(): void
    {
        $this->travelTo(now()->endOfDay());
        $user = $this->admin();
        $employee = Employee::factory()->create(['type' => Employee::STUDENT, 'valid_until' => now()->toDateString()]);
        $key = Key::factory()->create();
        $employee->borrowable_keys()->sync([$key->id]);

        $this->actingAs($user)->post(route('borrows.store'), [
            'employee_id' => $employee->id, 'keys' => [$key->id],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('borrow_key', [
            'borrow_id' => Borrow::latest('id')->firstOrFail()->id, 'key_id' => $key->id,
        ]);

        $borrow = Borrow::latest('id')->firstOrFail();
        $this->travelTo(now()->addDay()->startOfDay());
        $this->actingAs($user)->put(route('borrows.update', $borrow), [
            'employee_id' => $employee->id, 'keys' => [$key->id],
        ])->assertSessionHasErrors('employee_id');
    }

    public function test_update_ignores_own_borrow_but_rejects_other_occupations(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        [$x, $y] = Key::factory()->count(2)->create()->all();
        $a = $this->openBorrow($user, $employee, [$x->id]);
        $this->openBorrow($user, $employee, [$y->id]);

        $this->actingAs($user)->put(route('borrows.update', $a), [
            'employee_id' => $employee->id, 'keys' => [$x->id],
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('borrows.update', $a), [
            'employee_id' => $employee->id, 'keys' => [$x->id, $y->id],
        ])->assertSessionHasErrors('keys');
        $this->assertSame([$x->id], $a->keys()->pluck('keys.id')->all());
    }

    public function test_edit_keeps_existing_keys_and_allows_observation_updates_after_eligibility_is_removed(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDay(),
        ]);
        [$permissionRemovedKey, $expiredKey] = Key::factory()->count(2)->create()->all();
        $employee->borrowable_keys()->sync([$permissionRemovedKey->id, $expiredKey->id]);
        $withoutPermission = $this->openBorrow($user, $employee, [$permissionRemovedKey->id]);
        $withoutValidity = $this->openBorrow($user, $employee, [$expiredKey->id]);
        $employee->borrowable_keys()->detach($permissionRemovedKey->id);
        $employee->update(['valid_until' => now()->subDay()]);

        $this->actingAs($user)->get(route('borrows.edit', $withoutPermission))
            ->assertOk()->assertInertia(fn ($page) => $page->component('Keys/Borrow/Edit', false)
            ->where('keys', fn ($keys) => collect($keys)->pluck('id')->contains($permissionRemovedKey->id)));
        $this->actingAs($user)->put(route('borrows.update', $withoutPermission), [
            'employee_id' => $employee->id,
            'keys' => [$permissionRemovedKey->id],
            'observation' => 'Observação corrigida',
        ])->assertSessionHasNoErrors();
        $this->put(route('borrows.update', $withoutValidity), [
            'employee_id' => $employee->id,
            'keys' => [$expiredKey->id],
            'observation' => 'Outra observação corrigida',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Observação corrigida', $withoutPermission->fresh()->observation);
        $this->assertSame('Outra observação corrigida', $withoutValidity->fresh()->observation);
        $this->assertSame([$permissionRemovedKey->id], $withoutPermission->keys()->pluck('keys.id')->all());
        $this->assertSame([$expiredKey->id], $withoutValidity->keys()->pluck('keys.id')->all());
    }

    public function test_same_borrower_must_be_currently_eligible_for_new_keys(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDay(),
        ]);
        [$existingKey, $newKey] = Key::factory()->count(2)->create()->all();
        $employee->borrowable_keys()->attach($existingKey->id);
        $borrow = $this->openBorrow($user, $employee, [$existingKey->id]);

        $this->actingAs($user)->put(route('borrows.update', $borrow), [
            'employee_id' => $employee->id,
            'keys' => [$existingKey->id, $newKey->id],
        ])->assertSessionHasErrors('keys');

        $employee->borrowable_keys()->attach($newKey->id);
        $employee->update(['valid_until' => now()->subDay()]);
        $this->put(route('borrows.update', $borrow), [
            'employee_id' => $employee->id,
            'keys' => [$existingKey->id, $newKey->id],
        ])->assertSessionHasErrors('employee_id');

        $employee->update(['valid_until' => now()->addDay()]);
        $this->put(route('borrows.update', $borrow), [
            'employee_id' => $employee->id,
            'keys' => [$existingKey->id, $newKey->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$existingKey->id, $newKey->id], $borrow->fresh()->keys->pluck('id')->all());
    }

    public function test_changing_borrower_validates_all_keys_and_preserves_return_history(): void
    {
        $user = $this->admin();
        $previousEmployee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $newEmployee = Employee::factory()->create([
            'type' => Employee::EXTERNAL,
            'valid_until' => now()->addDay(),
        ]);
        [$returnedKey, $pendingKey] = Key::factory()->count(2)->create()->all();
        $borrow = $this->openBorrow($user, $previousEmployee, [$returnedKey->id, $pendingKey->id]);
        $receipt = Received::create([
            'borrow_id' => $borrow->id,
            'user_id' => $user->id,
            'receiver' => 'Recepção',
        ]);
        $receipt->keys()->attach($returnedKey->id);
        $newEmployee->borrowable_keys()->attach($pendingKey->id);

        $this->actingAs($user)->put(route('borrows.update', $borrow), [
            'employee_id' => $newEmployee->id,
            'keys' => [$returnedKey->id, $pendingKey->id],
        ])->assertSessionHasErrors('keys');

        $newEmployee->borrowable_keys()->attach($returnedKey->id);
        $this->put(route('borrows.update', $borrow), [
            'employee_id' => $newEmployee->id,
            'keys' => [$returnedKey->id, $pendingKey->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame($newEmployee->id, $borrow->fresh()->employee_id);
        $this->assertDatabaseHas('key_received', ['received_id' => $receipt->id, 'key_id' => $returnedKey->id]);
        $this->assertDatabaseHas('borrow_key', ['borrow_id' => $borrow->id, 'key_id' => $returnedKey->id]);
        $this->put(route('borrows.update', $borrow), [
            'employee_id' => $newEmployee->id,
            'keys' => [$pendingKey->id],
        ])->assertSessionHasErrors('keys');
    }

    public function test_return_remains_available_after_borrower_eligibility_is_removed(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDay(),
        ]);
        $key = Key::factory()->create();
        $employee->borrowable_keys()->attach($key->id);
        $borrow = $this->openBorrow($user, $employee, [$key->id]);
        $employee->borrowable_keys()->detach($key->id);
        $employee->update(['valid_until' => now()->subDay()]);

        $this->actingAs($user)->post(route('borrows.receive', $borrow), [
            'returned_by' => 'Recepção',
            'keys' => [$key->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('key_received', ['key_id' => $key->id]);
        $this->assertNotNull($borrow->fresh()->devolution);
    }

    public function test_preflight_and_transaction_validation_use_constant_queries_for_one_or_five_keys(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create(['type' => Employee::COLLABORATOR]);
        $count = 0;
        DB::listen(function (QueryExecuted $query) use (&$count) {
            if (str_contains($query->sql, 'borrow_key') && str_contains($query->sql, 'borrows')) {
                $count++;
            }
        });

        foreach ([1, 5] as $size) {
            $ids = Key::factory()->count($size)->create()->pluck('id')->all();
            $count = 0;
            $this->actingAs($user)->post(route('borrows.store'), [
                'employee_id' => $employee->id, 'keys' => $ids,
            ])->assertSessionHasNoErrors();
            $this->assertSame(2, $count);
        }
    }
}
