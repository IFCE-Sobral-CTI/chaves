<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RuleSeeder::class,
            PermissionSeeder::class,
        ]);

        User::factory(23)->create();

        User::factory()->create([
            'name' => 'Alan Freire',
            'email' => 'alan.freire@ifce.edu.br',
            'password' => Hash::make('qwe123'),
            'status' => 1,
            'registry' => 3024033,
            'permission_id' => Permission::where('description', 'Administrador')->first()->id
        ]);
    }
}
