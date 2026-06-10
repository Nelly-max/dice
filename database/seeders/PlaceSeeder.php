<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('physical_places')->insert([
            ['name' => 'Kimuka', 'physical_town_id' => 1],
            ['name' => 'Makongeni', 'physical_town_id' => 2],
            ['name' => 'Membly', 'physical_town_id' => 3],
            ['name' => 'Kware', 'physical_town_id' => 4],
        ]);
    }
}
