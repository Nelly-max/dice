<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HousePropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('house_property_types')->insert([
            ['name' => 'Apartment'],
            ['name' => 'Estate'],
            ['name' => 'Own Compound'],
            ['name' => 'Business Complex'],
        ]);
    }
}
