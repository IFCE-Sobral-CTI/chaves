<?php

namespace Database\Seeders;

use App\Models\Borrow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BorrowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Borrow::factory()->count(15)->create();
    }
}
