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
        Schema::connection('customer')->create('orders', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('order_id')->unique();

            // Source invoice
            $table->foreignId('checkout_invoice_id')
                ->constrained('checkout_invoices')
                ->cascadeOnDelete();

            // Customer
            $table->foreignId('customer_id')
                ->constrained('customer_accounts')
                ->cascadeOnDelete();

            // Copied from the invoice
            $table->string('payment_account')->unique();

            // Copied from the invoice
            $table->string('payment_method');

            // Financials
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);

            $table->enum('payment_status', [
                'cod',
                'partial-payment',
                'fully-paid',
                'overpaid',
                'refunded',
            ]);

            $table->enum('status', [
                'placed',
                'processing',
                'ready',
                'dispatched',
                'On-Transit',
                'Delivered',
                'completed',
                'disputed',
                'cancelled'
            ])->default('placed');

            // Customer delivery instructions
            $table->text('delivery_note')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('orders');
    }
};