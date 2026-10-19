<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Room;
use App\Models\Rule;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create([
            'permission_id' => Permission::where('description', 'Administrador')->firstOrFail()->id,
        ]);

        (new AuthServiceProvider($this->app))->boot(new Rule);

        return $user;
    }

    public function test_employee_profile_fields_and_formatted_phone_are_saved(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->post(route('employees.store'), [
            'type' => Employee::EMPLOYEE,
            'registry' => '987654321',
            'name' => 'Nome Completo',
            'usual_name' => 'Nome Usual',
            'occupation' => 'Técnico de laboratório',
            'email' => 'mutuario@example.test',
            'alternative_email' => '',
            'tel' => '(88) 99999-8888',
            'valid_until' => '',
            'observation' => 'Observação de cadastro',
        ])->assertRedirect();

        $employee = Employee::where('registry', '987654321')->firstOrFail();
        $this->assertSame('Nome Usual', $employee->usual_name);
        $this->assertSame('Técnico de laboratório', $employee->occupation);
        $this->assertSame('88999998888', $employee->tel);

        $this->actingAs($user)->put(route('employees.update', $employee), [
            'type' => Employee::EMPLOYEE,
            'registry' => '987654321',
            'name' => 'Nome Completo',
            'usual_name' => '',
            'occupation' => '',
            'email' => 'mutuario@example.test',
            'alternative_email' => '',
            'tel' => '(88) 3333-4444',
            'valid_until' => '',
            'observation' => 'Observação de cadastro',
        ])->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'usual_name' => null,
            'occupation' => null,
            'tel' => '8833334444',
        ]);
    }

    public function test_employee_search_matches_usual_name_occupation_and_observation(): void
    {
        $user = $this->administrator();
        $employee = Employee::factory()->create([
            'name' => 'Pessoa Exemplo',
            'registry' => '987654320',
            'usual_name' => 'Apelido Pesquisa',
            'occupation' => 'Ocupação Pesquisa',
            'observation' => 'Nota Pesquisa',
        ]);

        foreach (['Pessoa Exemplo', '987654320', 'Apelido Pesquisa', 'Ocupação Pesquisa', 'Nota Pesquisa'] as $term) {
            $this->actingAs($user)->get(route('employees.index', ['term' => $term]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('employees.data.0.id', $employee->id)
                    ->where('employees.data.0.usual_name', 'Apelido Pesquisa')
                    ->where('employees.data.0.occupation', 'Ocupação Pesquisa')
                    ->where('employees.data.0.observation', 'Nota Pesquisa'));
        }
    }

    public function test_employee_listing_keeps_access_control_and_pagination(): void
    {
        $user = $this->administrator();
        Employee::factory()->count(config('app.pagination') + 1)->create(['name' => 'PaginatedEmployee']);

        $this->actingAs($user)->get(route('employees.index', ['term' => 'PaginatedEmployee']))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('employees.data', config('app.pagination'))
                ->where('employees.total', config('app.pagination') + 1));

        $permission = Permission::factory()->create();
        $unauthorizedUser = User::factory()->create(['permission_id' => $permission->id]);
        $this->actingAs($unauthorizedUser)->get(route('employees.index'))->assertForbidden();
    }

    public function test_phone_with_no_digits_is_rejected_instead_of_silently_cleared(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->from(route('employees.create'))->post(route('employees.store'), [
            'type' => Employee::EMPLOYEE,
            'registry' => '987654322',
            'name' => 'Nome Completo',
            'email' => 'outro@example.test',
            'tel' => 'telefone inválido',
        ])->assertSessionHasErrors('tel');

        $this->assertDatabaseMissing('employees', ['registry' => '987654322']);
    }

    public function test_authorized_keys_are_validated_and_synchronized_for_restricted_profiles(): void
    {
        $user = $this->administrator();
        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        $keys = Key::factory()->count(2)->create(['room_id' => $room->id]);
        $payload = [
            'type' => Employee::STUDENT,
            'registry' => '987654323',
            'name' => 'Mutuário de Teste',
            'usual_name' => '',
            'occupation' => '',
            'email' => 'chaves@example.test',
            'alternative_email' => '',
            'tel' => '',
            'valid_until' => '2027-12-31',
            'observation' => '',
            'borrowable_key_ids' => [$keys[0]->id],
        ];

        $this->actingAs($user)->post(route('employees.store'), $payload)
            ->assertRedirect();

        $employee = Employee::where('registry', '987654323')->firstOrFail();
        $this->assertSame([$keys[0]->id], $employee->borrowable_keys()->pluck('keys.id')->map(fn ($id) => (int) $id)->all());

        $this->get(route('employees.edit', $employee))->assertOk()->assertInertia(fn ($page) => $page
            ->where('employee.borrowable_keys.0.id', $keys[0]->id)
            ->where('keys', fn ($options) => collect($options)->contains(fn ($option) => $option['name'] === $keys[0]->number.' - '.$room->description)));

        $payload['type'] = Employee::EXTERNAL;
        $payload['borrowable_key_ids'] = [$keys[1]->id];
        $this->put(route('employees.update', $employee), $payload)->assertRedirect();
        $this->assertSame([$keys[1]->id], $employee->fresh()->borrowable_keys()->pluck('keys.id')->map(fn ($id) => (int) $id)->all());

        $payload['type'] = Employee::EMPLOYEE;
        $payload['borrowable_key_ids'] = [$keys[0]->id];
        $this->put(route('employees.update', $employee), $payload)->assertRedirect();
        $this->assertSame([], $employee->fresh()->borrowable_keys()->pluck('keys.id')->map(fn ($id) => (int) $id)->all());

        $payload['type'] = Employee::EXTERNAL;
        $payload['registry'] = '987654327';
        $payload['email'] = 'chaves-vazias@example.test';
        $payload['borrowable_key_ids'] = [];
        $this->post(route('employees.store'), $payload)->assertRedirect();
        $this->assertSame([], Employee::where('registry', '987654327')->firstOrFail()->borrowable_keys()->pluck('keys.id')->all());
    }

    public function test_authorized_key_ids_must_exist_and_be_distinct(): void
    {
        $user = $this->administrator();
        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        $key = Key::factory()->create(['room_id' => $room->id]);
        $payload = [
            'type' => Employee::EXTERNAL,
            'registry' => '987654324',
            'name' => 'Mutuário de Teste',
            'email' => 'chaves-validacao@example.test',
            'tel' => '',
            'valid_until' => '2027-12-31',
        ];

        $this->actingAs($user)->from(route('employees.create'))->post(route('employees.store'), $payload + [
            'borrowable_key_ids' => [$key->id, $key->id],
        ])->assertSessionHasErrors('borrowable_key_ids.1');

        $payload['registry'] = '987654325';
        $payload['email'] = 'chaves-inexistente@example.test';
        $this->from(route('employees.create'))->post(route('employees.store'), $payload + [
            'borrowable_key_ids' => [999999],
        ])->assertSessionHasErrors('borrowable_key_ids.0');

        $payload['registry'] = '987654326';
        $payload['email'] = 'chaves-ausente@example.test';
        $this->from(route('employees.create'))->post(route('employees.store'), $payload)
            ->assertSessionHasErrors('borrowable_key_ids');

        $this->assertDatabaseMissing('employees', ['registry' => '987654324']);
        $this->assertDatabaseMissing('employees', ['registry' => '987654325']);
        $this->assertDatabaseMissing('employees', ['registry' => '987654326']);
    }

    public function test_employee_permission_updates_lock_keys_before_the_employee(): void
    {
        $user = $this->administrator();
        $key = Key::factory()->create();
        $employee = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => '2027-12-31',
        ]);
        $employee->borrowable_keys()->attach($key->id);
        $lockQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$lockQueries): void {
            if (str_contains(strtolower($query->sql), 'for update')) {
                $lockQueries[] = strtolower($query->sql);
            }
        });

        $this->actingAs($user)->put(route('employees.update', $employee), [
            'type' => Employee::STUDENT,
            'registry' => $employee->registry,
            'name' => $employee->name,
            'usual_name' => '',
            'occupation' => '',
            'email' => $employee->email,
            'alternative_email' => '',
            'tel' => '',
            'valid_until' => '2027-12-31',
            'observation' => '',
            'borrowable_key_ids' => [$key->id],
        ])->assertRedirect();

        $this->assertStringContainsString('from `keys`', $lockQueries[0]);
        $this->assertStringContainsString('from `employees`', $lockQueries[1]);
    }
}
