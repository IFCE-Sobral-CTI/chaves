<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_destroy_requires_authorization()
    {
        $viewerPermission = Permission::where('description', 'Visualizador')->first();
        $user = User::factory()->create([
            'permission_id' => $viewerPermission->id,
        ]);

        $activity = \Spatie\Activitylog\Models\Activity::create([
            'log_name' => 'default',
            'description' => 'test',
        ]);

        $this->actingAs($user)
            ->delete(route('activities.destroy', $activity))
            ->assertForbidden();
    }

    public function test_borrow_receive_rejects_foreign_key_ids()
    {
        $admin = User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->first()->id,
        ]);

        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        $employee = Employee::factory()->create();
        $key1 = Key::factory()->create(['room_id' => $room->id]);
        $key2 = Key::factory()->create(['room_id' => $room->id]);

        $borrow = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $admin->id,
        ]);
        $borrow->keys()->sync([$key1->id]);

        $this->actingAs($admin)
            ->post(route('borrows.receive', [
                'borrow' => $borrow->id,
                'keys' => $key2->id, // key2 não pertence ao borrow
            ]), ['returned_by' => 'João'])
            ->assertRedirect()
            ->assertSessionHas('flash');

        $borrow->refresh();
        $this->assertNull($borrow->devolution);
    }

    public function test_borrow_receive_persists_received_by_and_returned_by()
    {
        $admin = User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->first()->id,
        ]);

        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        $employee = Employee::factory()->create();
        $key = Key::factory()->create(['room_id' => $room->id]);

        $borrow = Borrow::factory()->create([
            'employee_id' => $employee->id,
            'user_id' => $admin->id,
        ]);
        $borrow->keys()->sync([$key->id]);

        $this->actingAs($admin)
            ->post(route('borrows.receive', [
                'borrow' => $borrow->id,
                'keys' => $key->id,
            ]), ['returned_by' => 'Maria'])
            ->assertRedirect();

        $borrow->refresh();
        $this->assertNotNull($borrow->devolution);
        $this->assertEquals($admin->id, $borrow->received_by);
        $this->assertEquals('Maria', $borrow->returned_by);
    }

    public function test_borrow_search_keeps_devolution_null_filter_with_term()
    {
        $admin = User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->first()->id,
        ]);

        $employee1 = Employee::factory()->create(['name' => 'João Silva']);
        $employee2 = Employee::factory()->create(['name' => 'Maria Souza']);

        Borrow::factory()->create([
            'employee_id' => $employee1->id,
            'user_id' => $admin->id,
            'devolution' => null,
        ]);

        Borrow::factory()->create([
            'employee_id' => $employee2->id,
            'user_id' => $admin->id,
            'devolution' => now(),
        ]);

        $result = Borrow::search(new \Illuminate\Http\Request(['term' => 'Silva']));

        $this->assertEquals(1, $result['count']);
        $this->assertTrue($result['borrows']->items()[0]->devolution === null);
    }
}
