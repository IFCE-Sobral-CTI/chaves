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
        $groups = Group::where('description', 'Páginas')->first();
        $permissions = Group::where('description', 'Permissões')->first();
        $rules = Group::where('description', 'Regras')->first();
        $users = Group::where('description', 'Usuários')->first();

        $data = [
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
