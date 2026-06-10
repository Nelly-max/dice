<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('listers')->insert([
            [
                'name' => 'Prime Realty Ltd',
                'phone' => '+254708275546',
                'email' => 'info@primerealty.co.ke',
                'photo' => 'img/admin.png',
                'type' => 'Company',
                'open_day_start' => 'Mon',
                'open_day_end' => 'Fri',
                'opening_time' => '08:00:00',
                'closing_time' => '17:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sunset Agencies',
                'phone' => '+254798765432',
                'email' => 'contact@sunsetagencies.co.ke',
                'photo' => 'img/admin.png',
                'type' => 'Agency',
                'open_day_start' => 'Mon',
                'open_day_end' => 'Sat',
                'opening_time' => '09:00:00',
                'closing_time' => '18:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'John Doe',
                'phone' => '+254700000001',
                'email' => 'john@example.com',
                'photo' => 'img/admin.png',
                'type' => 'Landlord',
                'open_day_start' => 'Mon',
                'open_day_end' => 'Sun',
                'opening_time' => '07:00:00',
                'closing_time' => '19:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
