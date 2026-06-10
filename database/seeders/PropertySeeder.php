<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('properties')->insert([
            [
                'title' => 'Hills View Estate',
                'listing_type' => 'onLease',
                'use_type' => 'residential', // 🏠 New field
                'description' => 'A serene gated community located 3km from Ngong Town with scenic views.',
                'property_category_id' => 1,
                'house_property_type_id' => 2,
                'lister_id' => 1,
            ],
            [
                'title' => 'Ngong Heights Apartments',
                'listing_type' => 'forRent',
                'use_type' => 'both', // 🏢 Can serve both uses
                'description' => 'Modern apartments offering comfort and convenience in Ngong.',
                'property_category_id' => 1,
                'house_property_type_id' => 1,
                'lister_id' => 2,
            ],
            [
                'title' => 'Roysambu Towers',
                'listing_type' => 'forRent',
                'use_type' => 'commercial', // 🏬 Strictly commercial
                'description' => 'Elegant high-rise apartments located in Roysambu, Nairobi.',
                'property_category_id' => 1,
                'house_property_type_id' => 1,
                'lister_id' => 3,
            ],
        ]);
    }
}
