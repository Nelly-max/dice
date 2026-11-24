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
        Schema::connection('customer')->create('checkout_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('checkout_invoice_id')
                ->constrained('checkout_invoices')
                ->cascadeOnDelete();

            /**
             * Original cart item
             */
            $table->foreignId('cart_item_id')
                ->nullable()
                ->constrained('cart')
                ->nullOnDelete();

            /**
             * Polymorphic stockable item.
             */
            $table->unsignedBigInteger('stockable_id');

            $table->string('stockable_type');

            /**
             * Business fulfilling the item.
             */
            $table->string('business_account');


            $table->unsignedBigInteger('sub_division_id')
                ->nullable()
                ->comment('Foreign key to hub.sub_divisions table');

            /**
             * Quantity ordered.
             */
            $table->unsignedInteger('quantity');

            $table->timestamps();

            // Cross-database foreign key constraint to the hub database
            $table->foreign('sub_division_id')
                  ->references('id')
                  ->on('hub.sub_divisions')
                  ->onDelete('set null');

            $table->index([
                'stockable_id',
                'stockable_type',
            ]);

            $table->index('business_account');

            $table->index('sub_division_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('checkout_items');
    }
};