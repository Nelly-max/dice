<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyUnitTypeImageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('property_unit_type_images')->insert([
            // Property-level images (no unit type)
            [
                'property_id' => 1,
                'house_unit_type_id' => 1,
                'image_path' => 'img/homeCity/H001VE001.png',
                'label' => 'Front View',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'property_id' => 1,
                'house_unit_type_id' => 2,
                'image_path' => 'img/homeCity/H001VE001.png',
                'label' => 'Living Room',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Unit-type-specific image
            [
                'property_id' => 1,
                'house_unit_type_id' => 1, // ID of the unit type
                'image_path' => 'img/homeCity/H001VE001.png',
                'label' => 'Bedroom View',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
