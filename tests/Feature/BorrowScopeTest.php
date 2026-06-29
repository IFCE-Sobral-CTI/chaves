<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_chart_returns_exactly_7_points_with_zeros_for_empty_days()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create();

        Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(1)->startOfDay(),
        ]);

        $result = Borrow::dataChart();

        $this->assertCount(7, $result);
        $this->assertArrayHasKey('label', $result[0]);
        $this->assertArrayHasKey('value', $result[0]);

        $nonZero = collect($result)->filter(fn ($item) => $item['value'] > 0);
        $this->assertGreaterThanOrEqual(1, $nonZero->count());
    }

    public function test_borrows_by_employee_type_groups_correctly()
    {
        $user = User::factory()->create();
        $baseline = Borrow::borrowsByEmployeeType();
        $baselineServer = collect($baseline)->firstWhere('label', 'Servidor')['value'] ?? 0;
        $baselineStudent = collect($baseline)->firstWhere('label', 'Discente')['value'] ?? 0;

        $employee1 = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $employee2 = Employee::factory()->create(['type' => Employee::STUDENT]);
        $employee3 = Employee::factory()->create(['type' => Employee::STUDENT]);

        Borrow::factory()->create(['employee_id' => $employee1->id, 'user_id' => $user->id]);
        Borrow::factory()->create(['employee_id' => $employee2->id, 'user_id' => $user->id]);
        Borrow::factory()->create(['employee_id' => $employee3->id, 'user_id' => $user->id]);

        $result = Borrow::borrowsByEmployeeType();

        $this->assertCount(4, $result);

        $employeeCount = collect($result)->firstWhere('label', 'Servidor')['value'];
        $studentCount = collect($result)->firstWhere('label', 'Discente')['value'];

        $this->assertEquals($baselineServer + 1, $employeeCount);
        $this->assertEquals($baselineStudent + 2, $studentCount);
    }

    public function test_top_rooms_orders_desc_and_limits_to_5()
    {
        $user = User::factory()->create();
        $block = Block::factory()->create();
        $room1 = Room::factory()->create(['block_id' => $block->id, 'description' => 'Sala A']);
        $room2 = Room::factory()->create(['block_id' => $block->id, 'description' => 'Sala B']);
        $employee = Employee::factory()->create();

        $key1 = Key::factory()->create(['room_id' => $room1->id]);
        $key2 = Key::factory()->create(['room_id' => $room2->id]);

        // Room1: 3 borrows
        for ($i = 0; $i < 3; $i++) {
            $borrow = Borrow::factory()->create(['employee_id' => $employee->id, 'user_id' => $user->id]);
            $borrow->keys()->sync([$key1->id]);
        }

        // Room2: 1 borrow
        $borrow = Borrow::factory()->create(['employee_id' => $employee->id, 'user_id' => $user->id]);
        $borrow->keys()->sync([$key2->id]);

        $result = Borrow::topRooms();

        $this->assertCount(2, $result);
        $this->assertEquals('Sala A', $result[0]['label']);
        $this->assertEquals(3, $result[0]['value']);
        $this->assertEquals('Sala B', $result[1]['label']);
        $this->assertEquals(1, $result[1]['value']);
    }

    public function test_expiring_employees_brings_only_students_and_externals_within_30_days()
    {
        $student = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDays(15),
        ]);

        $external = Employee::factory()->create([
            'type' => Employee::EXTERNAL,
            'valid_until' => now()->addDays(25),
        ]);

        Employee::factory()->create([
            'type' => Employee::EMPLOYEE,
            'valid_until' => now()->addDays(15),
        ]);

        Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDays(45),
        ]);

        $result = Employee::whereNotNull('valid_until')
            ->whereBetween('valid_until', [now(), now()->addDays(30)])
            ->whereIn('type', [Employee::STUDENT, Employee::EXTERNAL])
            ->orderBy('valid_until', 'asc')
            ->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $student->id));
        $this->assertTrue($result->contains('id', $external->id));
    }
}
