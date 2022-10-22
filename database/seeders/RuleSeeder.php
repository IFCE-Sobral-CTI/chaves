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
        $groups = Group::where('description', 'Grupos')->first();
        $permissions = Group::where('description', 'Permissões')->first();
        $rules = Group::where('description', 'Regras')->first();
        $users = Group::where('description', 'Usuários')->first();

        $data = [
            ['description' => 'Página incial de Grupos', 'control' => 'groups.viewAny', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar Grupo', 'control' => 'groups.view', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar Grupo', 'control' => 'groups.create', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar Grupo', 'control' => 'groups.update', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar Grupo', 'control' => 'groups.delete', 'group_id' => $groups->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página incial de Usuários', 'control' => 'users.viewAny', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar Usuário', 'control' => 'users.view', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar Usuário', 'control' => 'users.create', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar Usuário', 'control' => 'users.update', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar senha do Usuário', 'control' => 'users.update.password', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar Usuário', 'control' => 'users.delete', 'group_id' => $users->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página incial de Regras', 'control' => 'rules.viewAny', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar Regra', 'control' => 'rules.view', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar Regra', 'control' => 'rules.create', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar Regra', 'control' => 'rules.update', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar Regra', 'control' => 'rules.delete', 'group_id' => $rules->id, 'created_at' => now(), 'updated_at' => now()],

            ['description' => 'Página incial de Permissões', 'control' => 'permissions.viewAny', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Detalhar Permissão', 'control' => 'permissions.view', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Criar Permissão', 'control' => 'permissions.create', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Atualizar Permissão', 'control' => 'permissions.update', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Apagar Permissão', 'control' => 'permissions.delete', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Modificar regras de Permissão', 'control' => 'permissions.rules', 'group_id' => $permissions->id, 'created_at' => now(), 'updated_at' => now()],
        ];

        Rule::insert($data);
    }
}
