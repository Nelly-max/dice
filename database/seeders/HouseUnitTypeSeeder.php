<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HouseUnitTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('house_unit_types')->insert([
            ['name' => 'Single Room'],
            ['name' => 'Double Room'],
            ['name' => 'Bedsitter'],
            ['name' => '1 Bedroom'],
            ['name' => '2 Bedroom'],
            ['name' => '3 Bedroom'],
            ['name' => '4 Bedroom'],
        ]);
    }
}
