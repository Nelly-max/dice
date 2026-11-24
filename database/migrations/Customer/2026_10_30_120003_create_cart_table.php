<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('customer')->create('cart', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            
            $table->string('business_account')
                ->comment('References business.account to lock the item to a specific seller storefront');

            $table->unsignedBigInteger('stockable_id')
                ->comment('ID of the product in its subdivision table');
            
            $table->string('subdivision_code')
                ->comment('Short code for filtering (cooking_gas, liquor, water, etc.)');
                
            $table->unsignedBigInteger('sub_division_id')
                ->nullable()
                ->comment('Foreign key to hub.sub_divisions table');

            $table->unsignedBigInteger('shipment_type_id')
                ->nullable()
                ->comment('Foreign key to shipment_types table');
                
            $table->string('stockable_type')
                ->comment('Fully qualified model class name for polymorphic relationship');
            
            $table->integer('quantity')
                ->default(1)
                ->comment('Quantity selected by user');
            
            $table->timestamps();
            
            // Foreign key constraints (on the local 'customer' database connection)
            $table->foreign('shipment_type_id')
                  ->references('id')
                  ->on('shipment_types')
                  ->onDelete('set null');

            // Cross-database foreign key constraint to the hub database
            $table->foreign('sub_division_id')
                  ->references('id')
                  ->on('hub.sub_divisions')
                  ->onDelete('set null');
            
            // Indexes and Uniques
            $table->index(['user_id', 'session_id']);
            $table->index('subdivision_code');
            $table->index(['stockable_id', 'stockable_type']);
            $table->index(['business_account']);

            // Unique constraint matrix rules
            $table->unique([
                'user_id', 
                'session_id', 
                'business_account', 
                'stockable_id', 
                'stockable_type'
            ], 'cart_user_vendor_product_unique');
        });

        // Fixed to explicitly check and alter using the 'customer' connection context
        if (DB::connection('customer')->getDriverName() === 'mysql') {
            DB::connection('customer')->statement("ALTER TABLE cart COMMENT = 'Cart items with references to products and specific merchants'");
        }
    }

    public function down()
    {
        Schema::connection('customer')->dropIfExists('cart');
    }
};
