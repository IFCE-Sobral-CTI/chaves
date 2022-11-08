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
            GroupSeeder::class,
            RuleSeeder::class,
            PermissionSeeder::class,
            BlockSeeder::class,
            EmployeeSeeder::class,
            RoomSeeder::class,
            KeySeeder::class,
            BorrowSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Coordenação de Tecnologia da Informação',
            'email' => 'ti.sobral@ifce.edu.br',
            'password' => Hash::make('qwe123'),
            'status' => 1,
            'registry' => 123456,
            'permission_id' => Permission::where('description', 'Administrador')->first()->id
        ]);
    }
}
