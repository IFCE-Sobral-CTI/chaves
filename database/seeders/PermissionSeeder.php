<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Rule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        Permission::create(['description' => 'Administrador']);
        $viewer = Permission::create(['description' => 'Visualizador']);

        $viewer->rules()->sync(Rule::where('control', 'like', '%viewAny')->orWhere('control', 'users.profile')->pluck('id')->all());
    }
}
