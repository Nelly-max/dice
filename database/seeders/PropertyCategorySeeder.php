<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('property_categories')->insert([
            ['name' => 'House'],
            ['name' => 'Land'],
        ]);
    }
}
