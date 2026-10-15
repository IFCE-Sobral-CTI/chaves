<?php

namespace Tests\Feature;

use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Received;
use App\Models\Rule;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class BorrowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const CONTROLS = ['borrows.viewAny', 'borrows.view', 'borrows.create', 'borrows.update', 'borrows.delete', 'borrows.receive', 'borrows.receive.destroy'];

    protected function setUp(): void
    {
        parent::setUp();
        (new AuthServiceProvider($this->app))->boot(new Rule);
    }

    private function userWithRules(array $controls = [], bool $admin = false): User
    {
        $permission = Permission::factory()->create(['is_admin' => $admin]);
        $permission->rules()->attach(Rule::whereIn('control', $controls)->pluck('id'));

        return User::factory()->create(['permission_id' => $permission->id]);
    }

    /** @return array{Borrow, Received} */
    private function borrowWithReceipt(bool $closed = false): array
    {
        $borrow = Borrow::factory()->create([
            'user_id' => $this->userWithRules()->id,
            'employee_id' => Employee::factory()->create(['type' => Employee::EMPLOYEE])->id,
            'devolution' => $closed ? now()->toDateTimeString() : null,
        ]);
        $keys = Key::factory()->count(2)->create()->modelKeys();
        $borrow->keys()->attach($keys);
        $receipt = Received::create(['borrow_id' => $borrow->id, 'user_id' => $borrow->user_id, 'receiver' => 'Recepção']);
        $receipt->keys()->attach($closed ? $keys : [$keys[0]]);

        return [$borrow, $receipt];
    }

    private function snapshot(Borrow $borrow): array
    {
        return [
            $borrow->fresh()->getAttributes(),
            DB::table('borrow_key')->where('borrow_id', $borrow->id)->orderBy('id')->get()->toJson(),
            DB::table('receiveds')->where('borrow_id', $borrow->id)->orderBy('id')->get()->toJson(),
            DB::table('key_received')->join('receiveds', 'receiveds.id', '=', 'key_received.received_id')
                ->where('receiveds.borrow_id', $borrow->id)->select('key_received.*')->orderBy('key_received.id')->get()->toJson(),
            Borrow::count(), Received::count(), DB::table('borrow_key')->count(), DB::table('key_received')->count(), Activity::count(),
        ];
    }

    public function test_each_rule_grants_only_its_ability_and_admin_bypasses_the_matrix(): void
    {
        foreach (self::CONTROLS as $granted) {
            $user = $this->userWithRules([$granted]);
            foreach (self::CONTROLS as $ability) {
                $this->assertSame($granted === $ability, $user->can($ability, Borrow::class), "$granted => $ability");
            }
        }
        $none = $this->userWithRules();
        $admin = $this->userWithRules(admin: true);
        foreach (self::CONTROLS as $ability) {
            $this->assertFalse($none->can($ability, Borrow::class), $ability);
            $this->assertTrue($admin->can($ability, Borrow::class), $ability);
        }
    }

    public function test_all_borrow_routes_require_authentication_and_permission_without_side_effects(): void
    {
        [$borrow, $receipt] = $this->borrowWithReceipt();
        $viewer = $this->userWithRules();
        $before = $this->snapshot($borrow);
        $requests = [
            ['GET', route('borrows.index')], ['GET', route('borrows.create')],
            ['GET', route('borrows.show', $borrow)], ['GET', route('borrows.edit', $borrow)],
            ['POST', route('borrows.store')], ['PUT', route('borrows.update', $borrow)],
            ['POST', route('borrows.receive', $borrow)], ['DELETE', route('borrows.destroy', $borrow)],
            ['DELETE', route('borrows.receive.destroy', [$borrow, $receipt])],
        ];

        foreach ($requests as [$method, $url]) {
            $this->json($method, $url)->assertUnauthorized();
        }
        $this->actingAs($viewer);
        foreach ($requests as [$method, $url]) {
            $this->json($method, $url)->assertForbidden();
        }

        $this->assertSame($before, $this->snapshot($borrow));
    }

    public static function borrowStates(): array
    {
        return ['partial return' => [false], 'closed borrow' => [true]];
    }

    #[DataProvider('borrowStates')]
    public function test_receipt_deletion_accepts_only_the_registered_permission_for_non_admins(bool $closed): void
    {
        [$borrow, $receipt] = $this->borrowWithReceipt($closed);
        $operator = $this->userWithRules(['borrows.receive.destroy']);

        $this->actingAs($operator)->delete(route('borrows.receive.destroy', [$borrow, $receipt]))
            ->assertRedirect(route('borrows.show', $borrow))->assertSessionHas('flash.status', 'success');

        $this->assertModelMissing($receipt);
        $this->assertDatabaseMissing('key_received', ['received_id' => $receipt->id]);
        $this->assertDatabaseHas('borrows', ['id' => $borrow->id, 'devolution' => null, 'received_by' => null]);
        $activity = Activity::where('description', 'return_deleted')->where('subject_id', $borrow->id)->sole();
        $this->assertSame($operator->id, $activity->causer_id);
    }

    public function test_receive_and_borrow_delete_permissions_do_not_allow_receipt_deletion(): void
    {
        [$borrow, $receipt] = $this->borrowWithReceipt();
        $receiver = $this->userWithRules(['borrows.receive']);
        $remover = $this->userWithRules(['borrows.delete']);
        $before = $this->snapshot($borrow);

        foreach ([$receiver, $remover] as $operator) {
            $this->actingAs($operator)
                ->deleteJson(route('borrows.receive.destroy', [$borrow, $receipt]))->assertForbidden();
        }

        $this->assertSame($before, $this->snapshot($borrow));
    }

    public function test_show_exposes_receipt_deletion_separately_from_receiving(): void
    {
        [$borrow] = $this->borrowWithReceipt(closed: true);
        $receiver = $this->userWithRules(['borrows.view', 'borrows.receive']);
        $remover = $this->userWithRules(['borrows.view', 'borrows.receive.destroy']);

        $this->actingAs($receiver)->get(route('borrows.show', $borrow))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Keys/Borrow/Show', false)
                ->where('can.receive', true)->where('can.deleteReceived', false));
        $this->actingAs($remover)->get(route('borrows.show', $borrow))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Keys/Borrow/Show', false)
                ->where('can.receive', false)->where('can.deleteReceived', true));
    }

    public function test_receipt_deletion_permission_does_not_bypass_borrow_binding(): void
    {
        [$borrow, $receipt] = $this->borrowWithReceipt();
        [$other] = $this->borrowWithReceipt();
        $operator = $this->userWithRules(['borrows.receive.destroy']);
        $before = [$this->snapshot($borrow), $this->snapshot($other)];

        $this->actingAs($operator)
            ->deleteJson(route('borrows.receive.destroy', [$other, $receipt]))->assertNotFound();

        $this->assertSame($before, [$this->snapshot($borrow), $this->snapshot($other)]);
    }
}
