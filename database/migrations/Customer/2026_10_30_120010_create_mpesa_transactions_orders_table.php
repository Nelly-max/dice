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
        Schema::connection('customer')->create('mpesa_transactions_orders', function (Blueprint $table) {

            $table->id();

            // Related invoice
            $table->foreignId('checkout_invoice_id')
                ->constrained('checkout_invoices')
                ->cascadeOnDelete();

            // Payment account (used as the M-Pesa Account Reference)
            $table->string('payment_account');

            // STK request identifiers
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('merchant_request_id')->nullable();

            // M-Pesa transaction details
            $table->string('mpesa_receipt')->nullable()->unique();
            $table->decimal('amount', 12, 2);

            // Customer payment details
            $table->string('phone', 20)->nullable();

            // Payment method
            $table->enum('payment_method', [
                'stk_push',
                'paybill',
            ]);

            // Payment status
            $table->enum('payment_status', [
                'pending',
                'success',
                'failed',
                'cancelled',
                'timeout',
            ])->default('pending');

            $table->timestamp('transaction_date')->nullable();

            // Callback result
            $table->integer('result_code')->nullable();
            $table->text('result_desc')->nullable();

            // Raw Safaricom payloads
            $table->json('stk_request')->nullable();
            $table->json('callback_payload')->nullable();

            $table->timestamps();

            $table->index('checkout_invoice_id');
            $table->index('payment_account');
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('mpesa_transactions_orders');
    }
};