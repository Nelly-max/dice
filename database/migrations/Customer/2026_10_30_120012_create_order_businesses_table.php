<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('customer')->create('order_businesses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            /**
             * Business from the subdivision.
             */
            $table->string('business_account');

            $table->unsignedBigInteger('sub_division_id')
                ->nullable()
                ->comment('Foreign key to hub.sub_divisions table');

            $table->enum('status', [
                'pending',
                'accepted',
                'processing',
                'ready',
                'dispatched',
                'On-Transit',
                'delivered',
                'completed',
                'disputed',
                'cancelled',
            ])->default('pending');

            $table->timestamp('accepted_at')->nullable();

            $table->timestamp('dispatched_at')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            // Cross-database foreign key constraint to the hub database
            $table->foreign('sub_division_id')
                  ->references('id')
                  ->on('hub.sub_divisions')
                  ->onDelete('set null');

            $table->index('business_account');
            $table->index('sub_division_id');
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('order_businesses');
    }
};