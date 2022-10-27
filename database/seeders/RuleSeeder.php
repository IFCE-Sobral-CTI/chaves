<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Rule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $groups = Group::firstOrCreate(['description' => 'Páginas']);
        $permissions = Group::firstOrCreate(['description' => 'Permissões']);
        $rules = Group::firstOrCreate(['description' => 'Regras']);
        $users = Group::firstOrCreate(['description' => 'Usuários']);
        $blocks = Group::firstOrCreate(['description' => 'Blocos']);
        $employees = Group::firstOrCreate(['description' => 'Servidores']);
        $rooms = Group::firstOrCreate(['description' => 'Salas']);
        $keys = Group::firstOrCreate(['description' => 'Chaves']);
        $borrows = Group::firstOrCreate(['description' => 'Empréstimos']);

        $data = [
            ['description' => 'Página inicial', 'control' => 'borrows.viewAny', 'group_id' => $borrows->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'borrows.view', 'group_id' => $borrows->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'borrows.create', 'group_id' => $borrows->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'borrows.update', 'group_id' => $borrows->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'borrows.delete', 'group_id' => $borrows->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Receber chaves', 'control' => 'borrows.receive', 'group_id' => $borrows->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'keys.viewAny', 'group_id' => $keys->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'keys.view', 'group_id' => $keys->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'keys.create', 'group_id' => $keys->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'keys.update', 'group_id' => $keys->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'keys.delete', 'group_id' => $keys->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'employees.viewAny', 'group_id' => $employees->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'employees.view', 'group_id' => $employees->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'employees.create', 'group_id' => $employees->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'employees.update', 'group_id' => $employees->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'employees.delete', 'group_id' => $employees->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'rooms.viewAny', 'group_id' => $rooms->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'rooms.view', 'group_id' => $rooms->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'rooms.create', 'group_id' => $rooms->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'rooms.update', 'group_id' => $rooms->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'rooms.delete', 'group_id' => $rooms->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'blocks.viewAny', 'group_id' => $blocks->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'blocks.view', 'group_id' => $blocks->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'blocks.create', 'group_id' => $blocks->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'blocks.update', 'group_id' => $blocks->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'blocks.delete', 'group_id' => $blocks->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'groups.viewAny', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'groups.view', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'groups.create', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'groups.update', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'groups.delete', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'users.viewAny', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'users.view', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'users.create', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'users.update', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar de senha', 'control' => 'users.update.password', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'users.delete', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Perfil', 'control' => 'users.profile', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'rules.viewAny', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'rules.view', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'rules.create', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'rules.update', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'rules.delete', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página inicial', 'control' => 'permissions.viewAny', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar', 'control' => 'permissions.view', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar', 'control' => 'permissions.create', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar', 'control' => 'permissions.update', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar', 'control' => 'permissions.delete', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Modificar regras', 'control' => 'permissions.rules', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
        ];

        Rule::insert($data);
    }
}
