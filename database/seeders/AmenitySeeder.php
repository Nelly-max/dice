<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('amenities')->insert([
            ['name' => 'Parking'],
            ['name' => 'Swimming Pool'],
            ['name' => '24/7 Security'],
            ['name' => 'Wi-Fi'],
            ['name' => 'Gym'],
            ['name' => 'CCTV Surveillance'],
            ['name' => 'Backup Generator'],
            ['name' => 'Elevator'],
            ['name' => 'Laundry Area'],
            ['name' => 'Garden'],
        ]);
    }
}
