<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::connection('customer')->create('riders', function (Blueprint $table) {

            $table->id();


            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('customer_id')->unique();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customer_accounts')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Rider Account
            |--------------------------------------------------------------------------
            */

            $table->string('rider_account')
                  ->unique();



            /*
            |--------------------------------------------------------------------------
            | Identification
            |--------------------------------------------------------------------------
            */

            $table->string('national_id', 30)
                  ->unique();

            $table->string('license_number')
                  ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Personal Details
            |--------------------------------------------------------------------------
            */

            $table->string('name');
            
            $table->string('phone', 20)
            ->unique();
            
            $table->string('email');

            $table->date('date_of_birth')
                    ->nullable();


            
            /*
            |--------------------------------------------------------------------------
            | Payment Information
            |--------------------------------------------------------------------------
            */

            $table->string('mpesa_number', 20)
                  ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Rider Locality
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('county_id')
                  ->nullable();

            $table->unsignedBigInteger('town_id')
                  ->nullable();

            $table->unsignedBigInteger('place_id')
                  ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Account Status
            |--------------------------------------------------------------------------
            */

            $table->enum('account_status', [

                'pending',
                'processing',
                'business',
                'rejected',
                'active',
                'suspended',
                'blocked'

            ])->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();



            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('county_id');

            $table->index('town_id');

            $table->index('place_id');

        });
    }



    public function down(): void
    {
        Schema::connection('customer')
            ->dropIfExists('riders');
    }

};