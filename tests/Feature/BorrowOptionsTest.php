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
            $this->actingAs($user)->getJson(route("borrows.options.$resource.create"))->assertOk();
            $this->getJson(route("borrows.options.$resource.edit", $borrow))->assertForbidden();
        }
    }

    public function test_edit_permission_does_not_grant_creation_options(): void
    {
        $user = $this->userWithRule('borrows.update');
        $borrow = $this->borrow($user);

        foreach (['employees', 'keys'] as $resource) {
            $this->actingAs($user)->getJson(route("borrows.options.$resource.edit", $borrow))->assertOk();
            $this->getJson(route("borrows.options.$resource.create"))->assertForbidden();
            $this->getJson(route("borrows.options.$resource.edit", ['borrow' => Borrow::max('id') + 1]))->assertNotFound();
        }
    }

    public function test_employee_options_paginate_eligible_borrowers_and_omit_private_fields(): void
    {
        $this->travelTo(now()->startOfDay());
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
        $url = route('borrows.options.keys.create', ['term' => 'OptionsRoom']);

        $first = $this->actingAs($user)->getJson($url)->assertOk()
            ->assertJsonCount(20, 'data')->assertJsonPath('total', 21);
        $second = $this->getJson($url.'&page=2')->assertOk()->assertJsonCount(1, 'data');

        $rows = collect($first->json('data'))->concat($second->json('data'));
        $this->assertEqualsCanonicalizing($keys->slice(2)->pluck('id')->all(), $rows->pluck('id')->all());
        foreach ($rows as $row) {
            $this->assertSame(['id', 'number', 'room_id', 'room'], array_keys($row));
            $this->assertSame(['id', 'description'], array_keys($row['room']));
        }
        $this->getJson(route('borrows.options.keys.edit', ['borrow' => $borrow->id, 'term' => 'OptionsRoom']))
            ->assertOk()->assertJsonPath('total', 23);
        $this->getJson(route('borrows.options.keys.create', ['term' => "OptionsRoom ' OR 1=1 --"]))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_options_return_422_for_invalid_filters(): void
    {
        $user = $this->userWithRule('borrows.create');

        foreach (['employees', 'keys'] as $resource) {
            foreach ([['term' => ['invalid'], 'page' => 0], ['term' => str_repeat('a', 101), 'page' => 'invalid']] as $filters) {
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
                ->where('employees', [])->has('keys', 20)->missing('keys.0.observation')
                ->missing('keys.0.description')->missing('keys.0.room.observation'));
    }
}
