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
        Schema::connection('customer')->create('deliveries', function (Blueprint $table) {

            $table->id();

            // Delivery reference
            $table->string('delivery_number')->unique();

            // Order reference
            $table->unsignedBigInteger('order_id');

            // Business/shop details
            $table->string('business_account');

            // Assigned rider
            $table->string('rider_account')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Pickup Location
            |--------------------------------------------------------------------------
            */
            $table->string('pickup_name')->nullable();
            $table->string('pickup_phone')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Dropoff Location
            |--------------------------------------------------------------------------
            */
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('dropoff_latitude', 10, 7)->nullable();
            $table->decimal('dropoff_longitude', 10, 7)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Trip Information
            |--------------------------------------------------------------------------
            */
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('eta_minutes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Dispatch
            |--------------------------------------------------------------------------
            */
            $table->unsignedTinyInteger('dispatch_attempts')->default(0);

            $table->enum('dispatch_status', [
                'pending',
                'searching',
                'assigned',
                'retrying',
                'failed'
            ])->default('pending');

            $table->timestamp('next_retry_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Delivery Status
            |--------------------------------------------------------------------------
            */
            $table->enum('status', [
                'searching_rider',
                'rider_assigned',
                'rider_arrived_shop',
                'waiting_pickup',
                'picked_up',
                'on_transit',
                'delivered',
                'completed',
                'cancelled'
            ])->default('searching_rider');

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('arrived_shop_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->index(['status', 'business_account']);
            $table->index(['dispatch_status', 'next_retry_at']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('deliveries');
    }
};