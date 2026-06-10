<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TownSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('physical_towns')->insert([
            ['name' => 'Ngong', 'physical_county_id' => 1],
            ['name' => 'Thika', 'physical_county_id' => 2],
            ['name' => 'Ruiru', 'physical_county_id' => 2],
            ['name' => 'Mlolongo', 'physical_county_id' => 3],
        ]);
    }
}
