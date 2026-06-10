<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExtraChargesSeeder extends Seeder
{
    public function run()
    {
        DB::table('extra_charges')->insert([
            [ 'name' => 'Viewing Fee', 'created_at' => now(), 'updated_at' => now() ],
            [ 'name' => 'Garbage Collection', 'created_at' => now(), 'updated_at' => now() ],
        ]);
    }
}
