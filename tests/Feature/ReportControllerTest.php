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
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedRules(): void
    {
        $reportsGroup = \App\Models\Group::factory()->create(['description' => 'Relatórios']);
        Rule::factory()->create([
            'control' => 'reports.viewAny',
            'group_id' => $reportsGroup->id,
        ]);
    }

    private function createUserWithReportsAccess(): User
    {
        $this->seedRules();
        $permission = Permission::factory()->create();
        $rule = Rule::where('control', 'reports.viewAny')->firstOrFail();
        $permission->rules()->sync([$rule->id]);

        $user = User::factory()->create([
            'permission_id' => $permission->id,
            'status' => User::ACTIVE,
        ]);

        // Re-register gates after seeding
        $provider = new AuthServiceProvider(app());
        $provider->boot(new Rule);

        return $user;
    }

    private function createUserWithoutReportsAccess(): User
    {
        $permission = Permission::factory()->create();
        return User::factory()->create([
            'permission_id' => $permission->id,
            'status' => User::ACTIVE,
        ]);
    }

    public function test_hub_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_hub_renders_for_authorized_user()
    {
        $user = $this->createUserWithReportsAccess();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index', false)
            );
    }

    public function test_borrows_report_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();

        $this->actingAs($user)
            ->get(route('reports.borrows'))
            ->assertForbidden();
    }

    public function test_borrows_report_renders_with_correct_component()
    {
        $user = $this->createUserWithReportsAccess();

        $response = $this->actingAs($user)
            ->get(route('reports.borrows'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Borrows/Index', false)
            ->has('summary')
            ->has('borrows')
            ->has('users')
            ->has('employees')
            ->has('blocks')
            ->has('rooms')
            ->has('keys')
        );
    }

    public function test_borrows_filter_by_date_range()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $baselineCount = Borrow::whereBetween('created_at', [now()->subDays(10)->startOfDay(), now()->endOfDay()])->count();

        $borrowInRange = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);

        $borrowOutOfRange = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(20),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.borrows', [
                'start' => now()->subDays(10)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Borrows/Index', false)
            ->where('count', $baselineCount + 1)
        );
    }

    public function test_borrows_filter_by_end_date_only_regression_p2()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $baselineCount = Borrow::where('created_at', '<=', now()->subDays(5)->endOfDay())->count();

        $borrowBefore = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(10),
        ]);

        $borrowAfter = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.borrows', [
                'end' => now()->subDays(5)->format('Y-m-d'),
            ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Borrows/Index', false)
            ->where('count', $baselineCount + 1)
            ->where('borrows.data.0.id', $borrowBefore->id)
        );
    }

    public function test_borrows_situation_computed_in_backend()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $returned = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => now(),
        ]);

        $open = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(2),
        ]);

        $overdue = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(25),
        ]);

        // Verify via direct model access that the accessor works correctly
        $this->assertEquals('devolvido', $returned->fresh()->situation);
        $this->assertEquals('aberto', $open->fresh()->situation);
        $this->assertEquals('atrasado', $overdue->fresh()->situation);

        $response = $this->actingAs($user)
            ->get(route('reports.borrows'));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Borrows/Index', false)
        );
    }

    public function test_borrows_filter_by_situation()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $baselineReturned = Borrow::where('devolution', '!=', null)->count();

        $returned = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => now(),
        ]);

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(25),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.borrows', ['situation' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Borrows/Index', false)
            ->where('count', $baselineReturned + 1)
            ->where('borrows.data.0.situation', 'devolvido')
        );
    }

    public function test_borrows_filter_by_block_room_and_key()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();
        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        $key = Key::factory()->create(['room_id' => $room->id]);

        $borrowMatch = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);
        $borrowMatch->keys()->sync([$key->id]);

        $otherKey = Key::factory()->create();
        $borrowOther = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);
        $borrowOther->keys()->sync([$otherKey->id]);

        $baselineBlock = Borrow::whereHas('keys.room.block', fn ($q) => $q->where('blocks.id', $block->id))->count();
        $baselineRoom = Borrow::whereHas('keys.room', fn ($q) => $q->where('rooms.id', $room->id))->count();
        $baselineKey = Borrow::whereHas('keys', fn ($q) => $q->where('keys.id', $key->id))->count();

        $this->actingAs($user)
            ->get(route('reports.borrows', ['block' => $block->id]))
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Borrows/Index', false)
                ->where('count', $baselineBlock)
                ->where('borrows.data.0.id', $borrowMatch->id)
            );

        $this->actingAs($user)
            ->get(route('reports.borrows', ['room' => $room->id]))
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Borrows/Index', false)
                ->where('count', $baselineRoom)
                ->where('borrows.data.0.id', $borrowMatch->id)
            );

        $this->actingAs($user)
            ->get(route('reports.borrows', ['key' => $key->id]))
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Borrows/Index', false)
                ->where('count', $baselineKey)
                ->where('borrows.data.0.id', $borrowMatch->id)
            );
    }

    public function test_borrows_csv_export_respects_filters()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $baselineInRange = Borrow::whereBetween('created_at', [now()->subDays(10)->startOfDay(), now()->endOfDay()])->count();

        $borrowIn = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        $borrowOut = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(20),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.borrows', [
                'start' => now()->subDays(10)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
                'export_csv' => 1,
            ]));

        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="relatorio-emprestimos.csv"');

        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", $content));
        // Header + baseline + 1 new data row
        $this->assertCount($baselineInRange + 2, $lines);
        // BOM may prefix the first line; header values with spaces are quoted
        $firstLine = $lines[array_key_first($lines)];
        $this->assertStringContainsString('ID', $firstLine);
        $this->assertStringContainsString('Data Entrega', $firstLine);
    }

    public function test_borrows_summary_counts_are_correct()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $baselineTotal = Borrow::count();
        $baselineReturned = Borrow::where('devolution', '!=', null)->count();
        $baselineOpen = Borrow::where('devolution', null)->where('created_at', '>=', now()->subHours(\App\Models\Borrow::OVERDUE_AFTER_HOURS))->count();
        $baselineOverdue = Borrow::where('devolution', null)->where('created_at', '<', now()->subHours(\App\Models\Borrow::OVERDUE_AFTER_HOURS))->count();

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => now(),
        ]);

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(2),
        ]);

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(25),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.borrows'));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Borrows/Index', false)
            ->where('summary.total', $baselineTotal + 3)
            ->where('summary.returned', $baselineReturned + 1)
            ->where('summary.open', $baselineOpen + 1)
            ->where('summary.overdue', $baselineOverdue + 1)
            ->has('summary.keysMoved')
        );
    }

    public function test_overdue_report_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();

        $this->actingAs($user)
            ->get(route('reports.overdue'))
            ->assertForbidden();
    }

    public function test_overdue_report_renders_open_borrows()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $baselineOpen = Borrow::where('devolution', null)->count();
        $baselineOverdue = Borrow::where('devolution', null)->where('created_at', '<', now()->subHours(Borrow::OVERDUE_AFTER_HOURS))->count();

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(2),
        ]);

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(25),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.overdue'));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Overdue/Index', false)
            ->where('count', $baselineOpen + 2)
        );

        $props = $response->inertiaPage()['props'];
        $this->assertEquals($baselineOpen + 2, $props['summary']['total']);
        $this->assertEquals($baselineOverdue + 1, $props['summary']['overdue']);
    }

    public function test_overdue_csv_export()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        $baselineOpen = Borrow::where('devolution', null)->count();

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => null,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.overdue', ['export_csv' => 1]));

        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", $content));
        $this->assertCount($baselineOpen + 2, $lines);
    }

    public function test_expiring_access_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();

        $this->actingAs($user)
            ->get(route('reports.expiring-access'))
            ->assertForbidden();
    }

    public function test_expiring_access_shows_employees_near_expiration()
    {
        $user = $this->createUserWithReportsAccess();

        $student = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDays(15),
        ]);

        Employee::factory()->create([
            'type' => Employee::EXTERNAL,
            'valid_until' => now()->addDays(45),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.expiring-access'));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/ExpiringAccess/Index', false)
        );

        $props = $response->inertiaPage()['props'];
        $ids = collect($props['employees']['data'])->pluck('id');
        $this->assertContains($student->id, $ids->toArray());
    }

    public function test_expiring_access_filter_by_type()
    {
        $user = $this->createUserWithReportsAccess();

        $student = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDays(10),
        ]);

        Employee::factory()->create([
            'type' => Employee::EXTERNAL,
            'valid_until' => now()->addDays(10),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.expiring-access', ['type' => Employee::STUDENT]));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/ExpiringAccess/Index', false)
        );

        $props = $response->inertiaPage()['props'];
        $types = collect($props['employees']['data'])->pluck('type');
        $this->assertContains('Discente', $types->toArray());
        $this->assertNotContains('Externo', $types->toArray());
    }

    public function test_expiring_access_csv_export()
    {
        $user = $this->createUserWithReportsAccess();

        Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.expiring-access', ['export_csv' => 1]));

        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", $content));
        $this->assertGreaterThanOrEqual(2, count($lines));
        $firstLine = $lines[array_key_first($lines)];
        $this->assertStringContainsString('ID', $firstLine);
        $this->assertStringContainsString('Nome', $firstLine);
    }

    public function test_rooms_report_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();
        $this->actingAs($user)
            ->get(route('reports.rooms'))
            ->assertForbidden();
    }

    public function test_rooms_report_renders_with_data()
    {
        $user = $this->createUserWithReportsAccess();
        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        $key = Key::factory()->create(['room_id' => $room->id]);
        $employee = Employee::factory()->create();

        $borrow = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);
        $borrow->keys()->sync([$key->id]);

        $response = $this->actingAs($user)
            ->get(route('reports.rooms'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Rooms/Index', false)
            ->has('rooms.data')
            ->has('chart')
        );
    }

    public function test_employees_report_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();
        $this->actingAs($user)
            ->get(route('reports.employees'))
            ->assertForbidden();
    }

    public function test_employees_report_renders_with_data()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create(['type' => Employee::STUDENT]);

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.employees'));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Employees/Index', false)
            ->has('employees.data')
            ->has('chart')
        );
    }

    public function test_staff_report_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();
        $this->actingAs($user)
            ->get(route('reports.staff'))
            ->assertForbidden();
    }

    public function test_staff_report_renders_with_data()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.staff'));

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Staff/Index', false)
            ->has('staff.data')
        );
    }

    public function test_turnaround_report_requires_authorization()
    {
        $user = $this->createUserWithoutReportsAccess();
        $this->actingAs($user)
            ->get(route('reports.turnaround'))
            ->assertForbidden();
    }

    public function test_turnaround_report_renders_with_data()
    {
        $user = $this->createUserWithReportsAccess();
        $employee = Employee::factory()->create();

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'devolution' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.turnaround'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Turnaround/Index', false)
            ->has('turnaround')
            ->where('turnaround.0.category', 'Tipo de mutuário')
            ->has('turnaround.0.dimension')
            ->has('summary')
        );
    }

    public function test_start_date_in_future_is_rejected()
    {
        $user = $this->createUserWithReportsAccess();

        $this->actingAs($user)
            ->get(route('reports.borrows', ['start' => now()->addDay()->format('Y-m-d')]))
            ->assertSessionHasErrors('start');
    }
}
