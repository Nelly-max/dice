<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('customer')->create('daily_county_sequence', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Business Date
            |--------------------------------------------------------------------------
            | The day this county was first assigned a payment code.
            */
            $table->date('sequence_date');

            /*
            |--------------------------------------------------------------------------
            | County
            |--------------------------------------------------------------------------
            | References hub.physical_counties.id
            */
            $table->unsignedBigInteger('county_id');

            /*
            |--------------------------------------------------------------------------
            | Daily County Code
            |--------------------------------------------------------------------------
            | Starts at 10 every day.
            */
            $table->unsignedTinyInteger('daily_code');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            // A county can only have one code per day
            $table->unique(['sequence_date', 'county_id']);

            // A daily code can only be assigned once per day
            $table->unique(['sequence_date', 'daily_code']);

            $table->index('county_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('daily_county_sequence');
    }
};