<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Group::insert([
            ['description' => 'Páginas', 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Permissões', 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Regras', 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Usuários', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
