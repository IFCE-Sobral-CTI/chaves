<?php

namespace Database\Seeders;

use App\Models\Key;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Key::factory()->count(16)->create();
    }
}
