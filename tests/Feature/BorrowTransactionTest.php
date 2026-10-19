<?php

namespace Tests\Feature;

use App\Actions\Borrows\CreateBorrow;
use App\Actions\Borrows\DeleteBorrow;
use App\Actions\Borrows\DeleteReceived;
use App\Actions\Borrows\ReceiveKeys;
use App\Actions\Borrows\UpdateBorrow;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Received;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class BorrowTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Employee $employee;

    private Key $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['permission_id' => Permission::where('description', 'Administrador')->firstOrFail()->id]);
        $this->employee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $this->key = Key::factory()->create();
    }

    private function data(): array
    {
        return ['employee_id' => $this->employee->id, 'keys' => [$this->key->id], 'observation' => 'Original'];
    }

    private function borrow(): Borrow
    {
        return app(CreateBorrow::class)->handle($this->user, $this->data());
    }

    private function failAfterSql(string $fragment): void
    {
        $failed = false;
        DB::listen(function (QueryExecuted $query) use ($fragment, &$failed) {
            if (! $failed && str_contains($query->sql, $fragment)) {
                $failed = true;
                throw new RuntimeException('Injected write failure');
            }
        });
    }

    private function assertInjectedFailure(callable $operation): void
    {
        try {
            $operation();
            $this->fail('The injected failure was not triggered.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected write failure', $exception->getMessage());
        }
    }

    public function test_creation_rolls_back_borrow_pivot_and_audit_after_pivot_insert(): void
    {
        $borrows = Borrow::count();
        $pivots = DB::table('borrow_key')->count();
        $logs = DB::table('activity_log')->count();
        $this->failAfterSql('insert into `borrow_key`');

        $this->assertInjectedFailure(fn () => $this->borrow());

        $this->assertSame($borrows, Borrow::count());
        $this->assertSame($pivots, DB::table('borrow_key')->count());
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_return_rolls_back_receipt_pivot_and_audit_after_pivot_insert(): void
    {
        $borrow = $this->borrow();
        $receipts = Received::count();
        $logs = DB::table('activity_log')->count();
        $this->failAfterSql('insert into `key_received`');

        $this->assertInjectedFailure(fn () => app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção'));

        $this->assertSame($receipts, Received::count());
        $this->assertDatabaseMissing('key_received', ['key_id' => $this->key->id]);
        $this->assertNull($borrow->fresh()->devolution);
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_return_rolls_back_everything_if_closing_borrow_fails(): void
    {
        $borrow = $this->borrow();
        $logs = DB::table('activity_log')->count();
        $this->failAfterSql('update `borrows`');

        $this->assertInjectedFailure(fn () => app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção'));

        $this->assertDatabaseMissing('receiveds', ['borrow_id' => $borrow->id]);
        $this->assertDatabaseMissing('key_received', ['key_id' => $this->key->id]);
        $this->assertNull($borrow->fresh()->devolution);
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_edit_rolls_back_attributes_keys_and_audit(): void
    {
        $borrow = $this->borrow();
        $otherKey = Key::factory()->create();
        $logs = DB::table('activity_log')->count();
        $this->failAfterSql('insert into `borrow_key`');

        $this->assertInjectedFailure(fn () => app(UpdateBorrow::class)->handle($borrow, $this->user, [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id, $otherKey->id], 'observation' => 'Changed',
        ]));

        $this->assertSame('Original', $borrow->fresh()->observation);
        $this->assertSame([$this->key->id], $borrow->keys()->pluck('keys.id')->all());
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_delete_return_rolls_back_receipt_pivot_and_audit_if_reopening_fails(): void
    {
        $borrow = $this->borrow();
        $receipt = app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção');
        $logs = DB::table('activity_log')->count();
        $this->failAfterSql('update `borrows`');

        $this->assertInjectedFailure(fn () => app(DeleteReceived::class)->handle($borrow, $receipt, $this->user));

        $this->assertDatabaseHas('receiveds', ['id' => $receipt->id]);
        $this->assertDatabaseHas('key_received', ['received_id' => $receipt->id, 'key_id' => $this->key->id]);
        $this->assertNotNull($borrow->fresh()->devolution);
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_delete_borrow_rolls_back_cascades_and_audit(): void
    {
        $borrow = $this->borrow();
        $logs = DB::table('activity_log')->count();
        $this->failAfterSql('delete from `borrows`');

        $this->assertInjectedFailure(fn () => app(DeleteBorrow::class)->handle($borrow));

        $this->assertDatabaseHas('borrows', ['id' => $borrow->id]);
        $this->assertDatabaseHas('borrow_key', ['borrow_id' => $borrow->id, 'key_id' => $this->key->id]);
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_return_validates_body_without_writing_receipts_or_audit(): void
    {
        $borrow = $this->borrow();
        $logs = DB::table('activity_log')->count();
        $valid = ['returned_by' => 'Recepção', 'keys' => [$this->key->id]];
        $cases = [
            [['keys' => null], 'keys'],
            [['keys' => []], 'keys'],
            [['keys' => (string) $this->key->id], 'keys'],
            [['keys' => ['selected' => $this->key->id]], 'keys'],
            [['keys' => [$this->key->id, $this->key->id]], 'keys.0'],
            [['keys' => ['invalid']], 'keys.0'],
            [['keys' => [0]], 'keys.0'],
            [['keys' => [null]], 'keys.0'],
            [['keys' => [[$this->key->id]]], 'keys.0'],
            [['keys' => [Key::max('id') + 1]], 'keys'],
            [['returned_by' => null], 'returned_by'],
            [['returned_by' => ['Recepção']], 'returned_by'],
            [['returned_by' => 123], 'returned_by'],
            [['returned_by' => 'A'], 'returned_by'],
            [['returned_by' => str_repeat('A', 256)], 'returned_by'],
        ];

        foreach ($cases as [$invalid, $field]) {
            $this->actingAs($this->user)->from(route('borrows.show', $borrow))
                ->post(route('borrows.receive', $borrow), array_replace($valid, $invalid))
                ->assertRedirect(route('borrows.show', $borrow))->assertSessionHasErrors($field);
        }

        $this->assertSame(0, $borrow->received()->count());
        $this->assertDatabaseMissing('key_received', ['key_id' => $this->key->id]);
        $this->assertNull($borrow->fresh()->devolution);
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_return_authorizes_before_validating_body(): void
    {
        $borrow = $this->borrow();
        $viewer = User::factory()->create([
            'permission_id' => Permission::where('description', 'Visualizador')->firstOrFail()->id,
        ]);

        $this->actingAs($viewer)->postJson(route('borrows.receive', $borrow), [])->assertForbidden();
        $this->actingAs($viewer)->postJson(route('borrows.receive', $borrow), [
            'returned_by' => 'Recepção', 'keys' => [$this->key->id],
        ])->assertForbidden();

        $this->assertSame(0, $borrow->received()->count());
        $this->assertNull($borrow->fresh()->devolution);
    }

    public function test_partial_and_final_returns_use_key_lists_in_body(): void
    {
        $borrow = $this->borrow();
        $otherKey = Key::factory()->create();
        app(UpdateBorrow::class)->handle($borrow, $this->user, [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id, $otherKey->id],
        ]);

        foreach ([$this->key->id, $otherKey->id] as $index => $keyId) {
            $this->actingAs($this->user)->post(route('borrows.receive', $borrow), [
                'returned_by' => 'Recepção', 'keys' => [$keyId],
            ])->assertSessionHasNoErrors()->assertSessionHas('flash.status', 'success');

            $this->assertDatabaseHas('key_received', ['key_id' => $keyId]);
            $this->assertSame($index + 1, $borrow->received()->count());
            $this->assertSame($index === 0, $borrow->fresh()->devolution === null);
        }

        $this->assertSame($this->user->id, $borrow->fresh()->received_by);
        $this->assertSame('Recepção', $borrow->fresh()->returned_by);
    }

    public function test_duplicate_return_is_rejected_without_a_second_receipt(): void
    {
        $borrow = $this->borrow();
        app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção');

        $this->actingAs($this->user)->post(route('borrows.receive', ['borrow' => $borrow->id]), [
            'keys' => [$this->key->id],
            'returned_by' => 'Recepção',
        ])->assertSessionHasErrors('keys');

        $this->assertSame(1, $borrow->received()->count());
    }

    public function test_deleting_foreign_receipt_is_rejected(): void
    {
        $a = $this->borrow();
        $receipt = app(ReceiveKeys::class)->handle($a, $this->user, [$this->key->id], 'Recepção');
        $b = $this->borrow();

        $this->actingAs($this->user)->delete(route('borrows.receive.destroy', ['borrow' => $b->id, 'received' => $receipt->id]))->assertNotFound();

        $this->assertDatabaseHas('receiveds', ['id' => $receipt->id]);
        $this->assertNotNull($a->fresh()->devolution);
        $this->assertNull($b->fresh()->devolution);
    }

    public function test_deleting_return_does_not_reopen_key_in_another_borrow(): void
    {
        $a = $this->borrow();
        $receipt = app(ReceiveKeys::class)->handle($a, $this->user, [$this->key->id], 'Recepção');
        $b = $this->borrow();
        $logs = DB::table('activity_log')->count();

        $this->actingAs($this->user)->delete(route('borrows.receive.destroy', ['borrow' => $a->id, 'received' => $receipt->id]))
            ->assertSessionHas('flash.status', 'danger');

        $this->assertDatabaseHas('receiveds', ['id' => $receipt->id]);
        $this->assertNotNull($a->fresh()->devolution);
        $this->assertNull($b->fresh()->devolution);
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_eligibility_is_rechecked_using_current_employee_inside_transaction(): void
    {
        $this->employee->update(['type' => Employee::STUDENT, 'valid_until' => now()->addDay()]);
        $this->employee->borrowable_keys()->sync([$this->key->id]);
        // Simulate expiry/revocation after request preflight, keeping a stale model in memory.
        DB::table('employees')->where('id', $this->employee->id)->update(['valid_until' => now()->subDay()]);
        $before = Borrow::count();

        try {
            $this->borrow();
            $this->fail('Expired borrower was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('employee_id', $exception->errors());
        }
        $this->assertSame($before, Borrow::count());
    }

    public function test_authorization_is_rechecked_if_revoked_after_preflight(): void
    {
        $this->employee->update(['type' => Employee::STUDENT, 'valid_until' => now()->addDay()]);
        $this->employee->borrowable_keys()->attach($this->key->id);
        $revoked = false;
        DB::listen(function (QueryExecuted $query) use (&$revoked): void {
            if (! $revoked && str_contains($query->sql, 'from `borrowable_keys`')) {
                $revoked = true;
                DB::table('borrowable_keys')
                    ->where('employee_id', $this->employee->id)
                    ->where('key_id', $this->key->id)
                    ->delete();
            }
        });
        $before = Borrow::count();

        $this->actingAs($this->user)->post(route('borrows.store'), [
            'employee_id' => $this->employee->id,
            'keys' => [$this->key->id],
        ])->assertSessionHasErrors('keys');

        $this->assertTrue($revoked);
        $this->assertSame($before, Borrow::count());
        $this->assertDatabaseMissing('borrow_key', ['key_id' => $this->key->id]);
    }

    public function test_edit_cannot_remove_returned_keys_or_manually_close_borrow(): void
    {
        $borrow = $this->borrow();
        $otherKey = Key::factory()->create();
        app(UpdateBorrow::class)->handle($borrow, $this->user, [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id, $otherKey->id],
        ]);
        app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção');

        $this->actingAs($this->user)->put(route('borrows.update', $borrow), [
            'employee_id' => $this->employee->id, 'keys' => [$otherKey->id],
        ])->assertSessionHasErrors('keys');
        $this->actingAs($this->user)->put(route('borrows.update', $borrow), [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id, $otherKey->id], 'devolution' => now()->toDateTimeString(),
        ])->assertSessionHasErrors('devolution');

        $this->assertNull($borrow->fresh()->devolution);
        $this->assertCount(2, $borrow->keys()->get());
    }

    public function test_closed_borrow_can_be_described_after_its_key_is_borrowed_again(): void
    {
        $a = $this->borrow();
        app(ReceiveKeys::class)->handle($a, $this->user, [$this->key->id], 'Recepção');
        $this->borrow();

        $this->actingAs($this->user)->put(route('borrows.update', $a), array_merge($this->data(), ['observation' => 'Corrected']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Corrected', $a->fresh()->observation);
        $this->assertNotNull($a->fresh()->devolution);
    }

    public function test_edit_keeps_returned_keys_visible_after_they_are_lent_again(): void
    {
        $borrow = $this->borrow();
        app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção');
        $this->borrow();

        $this->actingAs($this->user)->get(route('borrows.edit', $borrow))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->component('Keys/Borrow/Edit', false)
            ->where('keys', fn ($keys) => collect($keys)->pluck('id')->contains($this->key->id)));
    }

    public function test_edit_cannot_leave_an_open_borrow_with_only_returned_keys(): void
    {
        $borrow = $this->borrow();
        $otherKey = Key::factory()->create();
        app(UpdateBorrow::class)->handle($borrow, $this->user, [
            'employee_id' => $this->employee->id, 'keys' => [$this->key->id, $otherKey->id],
        ]);
        app(ReceiveKeys::class)->handle($borrow, $this->user, [$this->key->id], 'Recepção');

        $this->actingAs($this->user)->put(route('borrows.update', $borrow), $this->data())
            ->assertSessionHasErrors('keys');

        $this->assertNull($borrow->fresh()->devolution);
        $this->assertCount(2, $borrow->keys()->get());
    }
}
