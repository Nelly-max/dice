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
        Schema::connection('customer')->create('order_customer_details', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customer_accounts')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Customer Snapshot
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->string('email')->nullable();

            $table->string('phone_number');

            $table->string('alternative_phone_number')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Delivery Details
            |--------------------------------------------------------------------------
            */

            $table->text('delivery_location');

            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();

            $table->text('delivery_note')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            // One customer snapshot per order
            $table->unique('order_id');

            $table->index('customer_id');
            $table->index('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('order_customer_details');
    }
};