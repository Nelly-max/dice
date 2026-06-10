<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillTypesSeeder extends Seeder
{
    public function run()
    {
        // Fetch bill IDs
        $electricityId = DB::table('bills')->where('name', 'Electricity')->value('id');
        $waterId       = DB::table('bills')->where('name', 'Water')->value('id');

        DB::table('bill_types')->insert([
            // Electricity Types
            [ 'bill_id' => $electricityId, 'type' => 'Meter', 'created_at' => now(), 'updated_at' => now() ],
            [ 'bill_id' => $electricityId, 'type' => 'Token', 'created_at' => now(), 'updated_at' => now() ],

            // Water Types
            [ 'bill_id' => $waterId,       'type' => 'Meter',  'created_at' => now(), 'updated_at' => now() ],
            [ 'bill_id' => $waterId,       'type' => 'Shared', 'created_at' => now(), 'updated_at' => now() ],
        ]);
    }
}
