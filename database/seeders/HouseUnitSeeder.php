<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HouseUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('house_units')->insert([
            // Property 1: Hills View Estate
            [
                'unit_number' => 'HV-1A',
                'price' => 20000,
                'status' => 'Vacant',
                'house_unit_type_id' => 4, // 1br
                'property_id' => 1,
            ],
            [
                'unit_number' => 'HV-1B',
                'price' => 25000,
                'status' => 'Occupied',
                'house_unit_type_id' => 5, // 2br
                'property_id' => 1,
            ],

            // Property 2: Ngong Heights
            [
                'unit_number' => 'NH-1A',
                'price' => 7000,
                'status' => 'Vacant',
                'house_unit_type_id' => 3, // Bedsitter
                'property_id' => 2,
            ],
            [
                'unit_number' => 'NH-1B',
                'price' => 15000,
                'status' => 'Booked',
                'house_unit_type_id' => 4, // 1br
                'property_id' => 2,
            ],
            [
                'unit_number' => 'NH-1C',
                'price' => 20000,
                'status' => 'Vacant',
                'house_unit_type_id' => 5, // 2br
                'property_id' => 2,
            ],

            // Property 3: KingsMen
            [
                'unit_number' => 'KM-1A',
                'price' => 10000,
                'status' => 'Vacant',
                'house_unit_type_id' => 2, // Single room
                'property_id' => 3,
            ],
            [
                'unit_number' => 'KM-1B',
                'price' => 40000,
                'status' => 'Occupied',
                'house_unit_type_id' => 7, // 4br
                'property_id' => 3,
            ],
        ]);
    }
}
