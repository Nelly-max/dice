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
        Schema::connection('finance')->create('rider_payment_profiles', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Rider
            |--------------------------------------------------------------------------
            |
            | Rider account identifier.
            |
            | Example:
            | R001A
            |
            */

            $table->string('rider_account');


            /*
            |--------------------------------------------------------------------------
            | Payment Method
            |--------------------------------------------------------------------------
            */

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnUpdate()
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Payment Details
            |--------------------------------------------------------------------------
            |
            | Send Money:
            |     payment_number
            |
            | Pochi:
            |     payment_number
            |
            | Till:
            |     payment_number
            |
            | Paybill:
            |     payment_number
            |     account_number
            |
            */

            $table->string('payment_number')->nullable();

            $table->string('account_number')->nullable();

            $table->string('account_name')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'active',
                'suspended',
                'blocked',
            ])->default('active');


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                'rider_account',
                'rider_account_index'
            );

            $table->index('payment_method_id');


            /*
            |--------------------------------------------------------------------------
            | One Payment Profile Per Rider
            |--------------------------------------------------------------------------
            |
            | A rider can only have ONE payment profile.
            |
            */

            $table->unique(
                'rider_account',
                'rider_payment_profile_unique'
            );
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('finance')
            ->dropIfExists('rider_payment_profiles');
    }
};