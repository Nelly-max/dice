<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            CountySeeder::class,
            TownSeeder::class,
            PlaceSeeder::class,
            PropertyCategorySeeder::class,
            HousePropertyTypeSeeder::class,
            AmenitySeeder::class,
            ListerSeeder::class,
            PropertySeeder::class,
            PropertyAmenitySeeder::class,
            HouseUnitTypeSeeder::class,
            HouseUnitSeeder::class,
            PropertyUnitTypeImageSeeder::class,
            ExtraChargesSeeder::class,
            BillsSeeder::class,
            BillTypesSeeder::class,
        ]);
    }
}


