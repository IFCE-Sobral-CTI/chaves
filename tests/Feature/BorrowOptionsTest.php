<?php

namespace Tests\Feature;

use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Received;
use App\Models\Room;
use App\Models\Rule;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AuthServiceProvider($this->app))->boot(new Rule);
    }

    private function userWithRule(string $control): User
    {
        $permission = Permission::factory()->create();
        $permission->rules()->attach(Rule::where('control', $control)->firstOrFail());

        return User::factory()->create(['permission_id' => $permission->id]);
    }

    /** @param array<int> $keyIds */
    private function borrow(User $user, array $keyIds = []): Borrow
    {
        $borrow = Borrow::factory()->create([
            'user_id' => $user->id,
            'employee_id' => Employee::factory()->create(['type' => Employee::EMPLOYEE])->id,
            'devolution' => null,
        ]);
        $borrow->keys()->attach($keyIds);

        return $borrow;
    }

    private function employee(): Employee
    {
        return Employee::factory()->create(['type' => Employee::EMPLOYEE]);
    }

    public function test_options_require_authentication_with_401(): void
    {
        $borrow = $this->borrow($this->userWithRule('borrows.update'));

        foreach (['employees', 'keys'] as $resource) {
            $this->getJson(route("borrows.options.$resource.create"))->assertUnauthorized();
            $this->getJson(route("borrows.options.$resource.edit", $borrow))->assertUnauthorized();
        }
    }

    public function test_creation_permission_does_not_grant_edit_options(): void
    {
        $user = $this->userWithRule('borrows.create');
        $borrow = $this->borrow($user);

        foreach (['employees', 'keys'] as $resource) {
            $url = route("borrows.options.$resource.create", $resource === 'keys' ? ['employee_id' => $this->employee()->id] : []);
            $this->actingAs($user)->getJson($url)->assertOk();
            $this->getJson(route("borrows.options.$resource.edit", $borrow))->assertForbidden();
        }
    }

    public function test_edit_permission_does_not_grant_creation_options(): void
    {
        $user = $this->userWithRule('borrows.update');
        $borrow = $this->borrow($user);

        foreach (['employees', 'keys'] as $resource) {
            $editRoute = $resource === 'keys'
                ? ['borrow' => $borrow->id, 'employee_id' => $borrow->employee_id]
                : ['borrow' => $borrow->id];
            $this->actingAs($user)->getJson(route("borrows.options.$resource.edit", $editRoute))->assertOk();
            $url = route("borrows.options.$resource.create", $resource === 'keys' ? ['employee_id' => $this->employee()->id] : []);
            $this->getJson($url)->assertForbidden();
            $this->getJson(route("borrows.options.$resource.edit", ['borrow' => Borrow::max('id') + 1]))->assertNotFound();
        }
    }

    public function test_employee_options_paginate_eligible_borrowers_and_omit_private_fields(): void
    {
        $this->travelTo(now()->endOfDay());
        $user = $this->userWithRule('borrows.create');
        $employees = Employee::factory()->count(21)->create([
            'name' => 'OptionsEmployee Available', 'type' => Employee::EMPLOYEE, 'valid_until' => null,
        ]);
        foreach ([Employee::EMPLOYEE, Employee::COLLABORATOR] as $type) {
            $employees->push(Employee::factory()->create([
                'name' => 'OptionsEmployee Legacy', 'type' => $type, 'valid_until' => now()->subDay(),
            ]));
        }
        foreach ([Employee::STUDENT, Employee::EXTERNAL] as $type) {
            $employees->push(Employee::factory()->create([
                'name' => 'OptionsEmployee Valid', 'type' => $type, 'valid_until' => now()->addDay(),
            ]));
            foreach ([null, now()->subDay()] as $validUntil) {
                Employee::factory()->create([
                    'name' => 'OptionsEmployee Invalid', 'type' => $type, 'valid_until' => $validUntil,
                ]);
            }
        }
        $employees->push(Employee::factory()->create([
            'name' => 'OptionsEmployee Boundary', 'type' => Employee::STUDENT, 'valid_until' => now(),
        ]));
        $url = route('borrows.options.employees.create', ['term' => 'OptionsEmployee']);

        $first = $this->actingAs($user)->getJson($url)->assertOk()
            ->assertJsonCount(20, 'data')->assertJsonPath('total', 26)->assertJsonPath('last_page', 2);
        $second = $this->getJson($url.'&page=2')->assertOk()->assertJsonCount(6, 'data');

        $rows = collect($first->json('data'))->concat($second->json('data'));
        $this->assertEqualsCanonicalizing($employees->pluck('id')->all(), $rows->pluck('id')->all());
        $this->assertSame(26, $rows->pluck('id')->unique()->count());
        foreach ($rows as $row) {
            $this->assertSame(['id', 'registry', 'name'], array_keys($row));
        }
        $this->getJson(route('borrows.options.employees.create', ['term' => $employees->first()->registry]))
            ->assertOk()->assertJsonFragment(['id' => $employees->first()->id]);
        $this->getJson(route('borrows.options.employees.create', ['term' => "OptionsEmployee ' OR 1=1 --"]))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_key_options_keep_partial_returns_correlated_and_preserve_own_edit_keys(): void
    {
        $user = $this->userWithRule('borrows.create');
        $user->permission->rules()->attach(Rule::where('control', 'borrows.update')->firstOrFail());
        $room = Room::factory()->create(['description' => 'OptionsRoom']);
        $keys = Key::factory()->count(23)->create(['room_id' => $room->id]);
        $borrow = $this->borrow($user, [$keys[0]->id, $keys[1]->id]);
        $receipt = Received::create(['borrow_id' => $borrow->id, 'user_id' => $user->id, 'receiver' => 'Recepção']);
        $receipt->keys()->attach($keys[0]->id);
        $this->borrow($user, [$keys[0]->id]);
        $url = route('borrows.options.keys.create', ['term' => 'OptionsRoom', 'employee_id' => $this->employee()->id]);

        $first = $this->actingAs($user)->getJson($url)->assertOk()
            ->assertJsonCount(20, 'data')->assertJsonPath('total', 21);
        $second = $this->getJson($url.'&page=2')->assertOk()->assertJsonCount(1, 'data');

        $rows = collect($first->json('data'))->concat($second->json('data'));
        $this->assertEqualsCanonicalizing($keys->slice(2)->pluck('id')->all(), $rows->pluck('id')->all());
        foreach ($rows as $row) {
            $this->assertSame(['id', 'number', 'room_id', 'room'], array_keys($row));
            $this->assertSame(['id', 'description'], array_keys($row['room']));
        }
        $this->getJson(route('borrows.options.keys.edit', ['borrow' => $borrow->id, 'employee_id' => $borrow->employee_id, 'term' => 'OptionsRoom']))
            ->assertOk()->assertJsonPath('total', 23);
        $this->getJson(route('borrows.options.keys.create', ['term' => "OptionsRoom ' OR 1=1 --", 'employee_id' => $this->employee()->id]))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_options_return_422_for_invalid_filters(): void
    {
        $user = $this->userWithRule('borrows.create');

        foreach (['employees', 'keys'] as $resource) {
            foreach ([['term' => ['invalid'], 'page' => 0], ['term' => str_repeat('a', 101), 'page' => 'invalid']] as $filters) {
                if ($resource === 'keys') {
                    $filters['employee_id'] = $this->employee()->id;
                }
                $this->actingAs($user)->getJson(route("borrows.options.$resource.create", $filters))
                    ->assertUnprocessable()->assertJsonValidationErrors(['term', 'page']);
            }
        }
    }

    public function test_initial_create_options_are_limited_and_do_not_expose_observations(): void
    {
        $user = $this->userWithRule('borrows.create');
        Key::factory()->count(21)->create(['observation' => 'Private note']);

        $this->actingAs($user)->get(route('borrows.create'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Keys/Borrow/Create', false)
                ->where('employees', [])->where('keys', [])->where('keysLastPage', 1));
    }

    public function test_creation_key_options_filter_authorized_and_available_keys_before_pagination(): void
    {
        $user = $this->userWithRule('borrows.create');
        $employee = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDay(),
        ]);
        $room = Room::factory()->create(['description' => 'RestrictedRoom']);
        $keys = Key::factory()->count(22)->create(['room_id' => $room->id]);
        $employee->borrowable_keys()->attach($keys->pluck('id'));
        $this->borrow($user, [$keys[0]->id]);
        $url = route('borrows.options.keys.create', ['employee_id' => $employee->id, 'term' => 'RestrictedRoom']);

        $first = $this->actingAs($user)->getJson($url)->assertOk()
            ->assertJsonCount(20, 'data')->assertJsonPath('total', 21);
        $second = $this->getJson($url.'&page=2')->assertOk()->assertJsonCount(1, 'data');
        $this->assertEqualsCanonicalizing($keys->slice(1)->pluck('id')->all(), collect($first->json('data'))
            ->concat($second->json('data'))->pluck('id')->all());

        $employee->update(['valid_until' => now()->subDay()]);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('total', 0);
    }

    public function test_creation_key_options_are_empty_without_validity_or_authorized_keys(): void
    {
        $user = $this->userWithRule('borrows.create');

        foreach ([
            Employee::factory()->create(['type' => Employee::STUDENT, 'valid_until' => null]),
            Employee::factory()->create(['type' => Employee::EXTERNAL, 'valid_until' => now()->addDay()]),
        ] as $employee) {
            $this->actingAs($user)
                ->getJson(route('borrows.options.keys.create', ['employee_id' => $employee->id]))
                ->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('total', 0);
        }
    }

    public function test_creation_key_options_keep_all_available_keys_for_collaborators(): void
    {
        $user = $this->userWithRule('borrows.create');
        $room = Room::factory()->create(['description' => 'CollaboratorRoom']);
        $keys = Key::factory()->count(2)->create(['room_id' => $room->id]);
        $this->borrow($user, [$keys[0]->id]);

        foreach ([Employee::EMPLOYEE, Employee::COLLABORATOR] as $type) {
            $employee = Employee::factory()->create(['type' => $type, 'valid_until' => null]);
            $this->actingAs($user)
                ->getJson(route('borrows.options.keys.create', ['employee_id' => $employee->id, 'term' => 'CollaboratorRoom']))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $keys[1]->id);
        }
    }

    public function test_edit_key_options_keep_existing_keys_and_filter_new_keys_for_selected_borrower(): void
    {
        $user = $this->userWithRule('borrows.update');
        $originalEmployee = Employee::factory()->create(['type' => Employee::EMPLOYEE]);
        $targetEmployee = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDay(),
        ]);
        $room = Room::factory()->create(['description' => 'EditAuthorizationRoom']);
        [$existingKey, $authorizedKey, $unauthorizedKey] = Key::factory()->count(3)->create(['room_id' => $room->id])->all();
        $borrow = Borrow::factory()->create([
            'user_id' => $user->id,
            'employee_id' => $originalEmployee->id,
            'devolution' => null,
        ]);
        $borrow->keys()->attach($existingKey->id);
        $targetEmployee->borrowable_keys()->attach($authorizedKey->id);
        $url = route('borrows.options.keys.edit', [
            'borrow' => $borrow->id,
            'employee_id' => $targetEmployee->id,
            'term' => 'EditAuthorizationRoom',
        ]);

        $response = $this->actingAs($user)->getJson($url)->assertOk()->assertJsonPath('total', 2);
        $this->assertEqualsCanonicalizing([$existingKey->id, $authorizedKey->id], collect($response->json('data'))->pluck('id')->all());

        $targetEmployee->update(['valid_until' => now()->subDay()]);
        $response = $this->getJson($url)->assertOk()->assertJsonPath('total', 1);
        $this->assertSame([$existingKey->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_store_rejects_a_key_after_its_authorization_is_removed(): void
    {
        $user = $this->userWithRule('borrows.create');
        $employee = Employee::factory()->create([
            'type' => Employee::STUDENT,
            'valid_until' => now()->addDay(),
        ]);
        $key = Key::factory()->create();
        $employee->borrowable_keys()->attach($key->id);
        $borrowCount = Borrow::count();

        $this->actingAs($user)
            ->getJson(route('borrows.options.keys.create', ['employee_id' => $employee->id]))
            ->assertOk()->assertJsonFragment(['id' => $key->id]);
        $employee->borrowable_keys()->detach($key->id);

        $this->post(route('borrows.store'), ['employee_id' => $employee->id, 'keys' => [$key->id]])
            ->assertSessionHasErrors('keys');
        $this->assertDatabaseCount('borrows', $borrowCount);
    }

    public function test_creation_key_options_require_a_valid_employee(): void
    {
        $user = $this->userWithRule('borrows.create');

        $this->actingAs($user)->getJson(route('borrows.options.keys.create'))
            ->assertUnprocessable()->assertJsonValidationErrors('employee_id');
        $this->getJson(route('borrows.options.keys.create', ['employee_id' => 999999]))
            ->assertUnprocessable()->assertJsonValidationErrors('employee_id');
    }
}
