<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Room;
use App\Models\Rule;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_use_the_requested_page_and_keep_the_term_in_pagination_links(): void
    {
        $administratorPermission = Permission::where('description', 'Administrador')->firstOrFail();
        $administrator = User::factory()->create([
            'name' => 'Administrator',
            'permission_id' => $administratorPermission->id,
        ]);
        (new AuthServiceProvider($this->app))->boot(new Rule);

        $pageSize = config('app.pagination');
        $count = $pageSize + 1;
        $block = Block::factory()->create();
        $room = Room::factory()->create(['block_id' => $block->id]);
        Block::factory()->count($count)->sequence(fn (Sequence $sequence): array => [
            'description' => 'ResultadoBuscaBloco '.$sequence->index,
        ])->create();
        Employee::factory()->count($count)->create(['name' => 'ResultadoBuscaMutuario']);
        $borrowEmployee = Employee::factory()->create(['name' => 'ResultadoBuscaEmprestimo']);
        Borrow::factory()->count($count)->create([
            'employee_id' => $borrowEmployee->id,
            'user_id' => $administrator->id,
            'devolution' => null,
        ]);
        Group::factory()->count($count)->create(['description' => 'ResultadoBuscaGrupo']);
        Key::factory()->count($count)->create([
            'description' => 'ResultadoBuscaChave',
            'room_id' => $room->id,
        ]);
        Room::factory()->count($count)->create([
            'description' => 'ResultadoBuscaSala',
            'block_id' => $block->id,
        ]);
        User::factory()->count($pageSize + 1)->create(['name' => 'ResultadoBuscaUsuario']);
        $group = Group::factory()->create();
        Rule::factory()->count($pageSize + 1)->create([
            'description' => 'ResultadoBuscaRegra',
            'group_id' => $group->id,
        ]);
        Permission::factory()->count($pageSize + 1)->create(['description' => 'ResultadoBuscaPermissao']);
        foreach (range(1, $count) as $index) {
            Activity::create([
                'log_name' => 'default',
                'description' => 'Busca activity '.$index,
                'subject_type' => 'ResultadoBuscaAtividade',
            ]);
        }

        foreach ([
            ['activities.index', 'activities', 'ResultadoBuscaAtividade'],
            ['blocks.index', 'blocks', 'ResultadoBuscaBloco'],
            ['borrows.index', 'borrows', 'ResultadoBuscaEmprestimo'],
            ['employees.index', 'employees', 'ResultadoBuscaMutuario'],
            ['groups.index', 'groups', 'ResultadoBuscaGrupo'],
            ['keys.index', 'keys', 'ResultadoBuscaChave'],
            ['users.index', 'users', 'ResultadoBuscaUsuario'],
            ['rules.index', 'rules', 'ResultadoBuscaRegra'],
            ['permissions.index', 'permissions', 'ResultadoBuscaPermissao'],
            ['rooms.index', 'rooms', 'ResultadoBuscaSala'],
        ] as [$route, $collection, $term]) {
            $this->actingAs($administrator)
                ->get(route($route, ['term' => $term, 'page' => 2]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('page', 2)
                    ->where('termSearch', $term)
                    ->where($collection.'.current_page', 2)
                    ->where($collection.'.total', $pageSize + 1)
                    ->where($collection.'.links', fn ($links) => collect($links)->pluck('url')->filter()
                        ->contains(fn ($url) => str_contains($url, 'term='.$term) && str_contains($url, 'page=2'))));
        }
    }
}
