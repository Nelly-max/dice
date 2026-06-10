<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillsSeeder extends Seeder
{
    public function run()
    {
        DB::table('bills')->insert([
            [ 'name' => 'Electricity', 'created_at' => now(), 'updated_at' => now() ],
            [ 'name' => 'Water',       'created_at' => now(), 'updated_at' => now() ],
        ]);
    }
}
