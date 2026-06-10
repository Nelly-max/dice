<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('physical_counties')->insert([
            ['name' => 'Kajiado', 'country_id' => 1],
            ['name' => 'Kiambu', 'country_id' => 1],
            ['name' => 'Machakos', 'country_id' => 1],
        ]);
    }
}
