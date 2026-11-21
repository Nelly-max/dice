<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('customer')->create('sales', function (Blueprint $table) {

            $table->id();

            $table->string('reference')->unique();

            $table->unsignedBigInteger('sub_division_id')
                ->nullable();

            $table->unsignedBigInteger('subdivision_sale_id');

            $table->string('business_account');

            $table->string('customer_account')->nullable();

            $table->unsignedBigInteger('shop_user_id');

            $table->enum('customer_type',[
                'walk_in',
                'account',
            ])->default('walk_in');

            $table->enum('purchase_channel',[
                'shop',
                'order',
            ]);

            $table->enum('status',[
                'draft',
                'pending_payment',
                'completed',
                'cancelled',
                'refunded',
                'partially_refunded'
            ])->default('completed');

            $table->enum('payment_status',[
                'pending',
                'partial',
                'paid',
                'refunded'
            ])->default('paid');

            $table->decimal('subtotal',15,2)->default(0);

            $table->decimal('discount',15,2)->default(0);

            $table->decimal('total',15,2)->default(0);

            $table->timestamps();

            // Cross-database foreign key constraint to the hub database
            $table->foreign('sub_division_id')
                  ->references('id')
                  ->on('hub.sub_divisions')
                  ->onDelete('set null');

            $table->index('reference');
            $table->index('customer_account');
            $table->index('business_account');
            $table->index('sub_division_id');
            $table->index('created_at');

        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('sales');
    }
};