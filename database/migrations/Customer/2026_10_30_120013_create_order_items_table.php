<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('customer')->create('order_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('order_business_id')
                ->constrained('order_businesses')
                ->cascadeOnDelete();

            /**
             * Original checkout item.
             */
            $table->foreignId('checkout_item_id')
                ->constrained('checkout_items')
                ->cascadeOnDelete();

            /**
             * Original cart item.
             */
            $table->foreignId('cart_item_id')
                ->nullable()
                ->constrained('cart')
                ->nullOnDelete();

            /**
             * Purchased stockable.
             */
            $table->unsignedBigInteger('stockable_id');

            $table->string('stockable_type');

            /**
             * Business fulfilling this item.
             */
            $table->string('business_account');

            $table->unsignedInteger('quantity');

            $table->decimal('unit_price', 12, 2);

            $table->decimal('subtotal', 12, 2);

            $table->timestamps();

            $table->index([
                'stockable_id',
                'stockable_type',
            ]);

            $table->index('business_account');
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('order_items');
    }
};