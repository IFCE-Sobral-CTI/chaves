<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Room;
use App\Models\Rule;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithoutBorrowViewAny(): User
    {
        $permission = Permission::factory()->create(['description' => 'Sem Acesso']);

        return User::factory()->create([
            'permission_id' => $permission->id,
        ]);
    }

    private function createUserWithBorrowViewAny(): User
    {
        $permission = Permission::where('description', 'Visualizador')->firstOrFail();

        return User::factory()->create([
            'permission_id' => $permission->id,
        ]);
    }

    public function test_user_without_borrow_view_any_receives_kpis_and_charts_but_empty_lists()
    {
        $user = $this->createUserWithoutBorrowViewAny();

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard', false)
                ->has('kpis')
                ->has('totals')
                ->has('charts')
                ->where('recentBorrows', [])
                ->where('overdueList', [])
                ->where('expiringEmployees', [])
            );
    }

    public function test_user_with_borrow_view_any_receives_detailed_lists()
    {
        $user = $this->createUserWithBorrowViewAny();
        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        $employee = Employee::factory()->create();
        $key = Key::factory()->create(['room_id' => $room->id]);

        $borrow = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
        ]);
        $borrow->keys()->sync([$key->id]);

        // Re-register gates after seeding (AuthServiceProvider booted before seed)
        $provider = new AuthServiceProvider(app());
        $provider->boot(new Rule);

        $this->assertTrue($user->can('borrows.viewAny'), 'User should have borrows.viewAny');

        $response = $this->actingAs($user)
            ->get(route('admin'));

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard', false)
            ->has('recentBorrows')
            ->has('overdueList')
            ->has('expiringEmployees')
        );

        $props = $response->inertiaPage()['props'];
        $this->assertNotEmpty($props['recentBorrows'], 'recentBorrows should not be empty');
    }

    public function test_kpis_keys_consistency()
    {
        $user = $this->createUserWithoutBorrowViewAny();
        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        Key::factory()->count(5)->create(['room_id' => $room->id]);

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard', false)
                ->where('kpis.keysOut', fn ($value) => is_int($value) && $value >= 0)
                ->where('totals.countKeys', fn ($value) => is_int($value) && $value >= 5)
                ->where('kpis.keysAvailable', fn ($value) => is_int($value) && $value >= 0)
            );
    }

    public function test_overdue_borrows_count_only_open_older_than_24h()
    {
        $user = $this->createUserWithoutBorrowViewAny();
        $employee = Employee::factory()->create();

        $baselineOpen = Borrow::whereNull('devolution')->count();
        $baselineOverdue = Borrow::whereNull('devolution')->where('created_at', '<', now()->subDay())->count();

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subDays(2),
        ]);

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(2),
        ]);

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard', false)
                ->where('kpis.openBorrows', $baselineOpen + 2)
                ->where('kpis.overdueBorrows', $baselineOverdue + 1)
            );
    }
}
