<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyAmenitySeeder extends Seeder
{
    public function run(): void
    {
        // Example data linking amenities to properties
        // Make sure you have properties in the `properties` table first!

        DB::table('property_amenity')->insert([
            ['amenity_id' => 1, 'property_id' => 1],
            ['amenity_id' => 2, 'property_id' => 1],
            ['amenity_id' => 3, 'property_id' => 1],

            ['amenity_id' => 1, 'property_id' => 2],
            ['amenity_id' => 4, 'property_id' => 2],
            ['amenity_id' => 5, 'property_id' => 2],

            ['amenity_id' => 6, 'property_id' => 3],
            ['amenity_id' => 7, 'property_id' => 3],
            ['amenity_id' => 8, 'property_id' => 3],
        ]);
    }
}
