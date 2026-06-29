<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Rule;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = new Permission(['description' => 'Administrador']);
        $admin->is_admin = true; // is_admin não é fillable; definido explicitamente aqui
        $admin->save();

        $viewer = Permission::create(['description' => 'Visualizador']);

        $viewer->rules()->sync(Rule::where('control', 'like', '%viewAny')->orWhere('control', 'users.profile')->pluck('id')->all());
    }
}
