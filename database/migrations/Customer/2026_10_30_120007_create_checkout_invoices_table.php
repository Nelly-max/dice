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
        Schema::connection('customer')->create('checkout_invoices', function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Invoice Identification
                |--------------------------------------------------------------------------
                */

                $table->ulid('uuid')->unique();

                $table->string('invoice_number')
                    ->unique();

                $table->unsignedInteger('sequence_number');

                /*
                |--------------------------------------------------------------------------
                | Payment Account
                |--------------------------------------------------------------------------
                */

                $table->string('payment_account')
                    ->index();

                /*
                |--------------------------------------------------------------------------
                | Customer
                |--------------------------------------------------------------------------
                */

                $table->foreignId('customer_id')
                    ->constrained('customer_accounts')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Customer Snapshot
                |--------------------------------------------------------------------------
                */

                $table->string('name');

                $table->string('email')->nullable();

                $table->string('phone_number');

                $table->string('alternative_phone_number')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Delivery Details
                |--------------------------------------------------------------------------
                */

                $table->text('delivery_location');

                $table->decimal('latitude', 10, 7)
                    ->nullable();

                $table->decimal('longitude', 10, 7)
                    ->nullable();

                $table->text('delivery_note')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Receiving County
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger('county_id');

                $table->unsignedInteger('county_sequence');

                /*
                |--------------------------------------------------------------------------
                | Payment Information
                |--------------------------------------------------------------------------
                */

                $table->string('payment_method');

                $table->decimal('subtotal', 12, 2)
                    ->default(0);

                $table->decimal('delivery_fee', 12, 2)
                    ->default(0);

                $table->decimal('discount', 12, 2)
                    ->default(0);

                $table->decimal('total_amount', 12, 2)
                    ->default(0);

                $table->decimal('amount_paid', 12, 2)
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Invoice Status
                |--------------------------------------------------------------------------
                */

                $table->enum('status', [
                    'pending',
                    'partial',
                    'order-placed',
                    'cancelled',
                    'expired',
                ])->default('pending');


                /*
                |--------------------------------------------------------------------------
                | Payment Confirmation
                |--------------------------------------------------------------------------
                */

                $table->timestamp('paid_at')
                    ->nullable();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index('customer_id');
                $table->index('phone_number');
                $table->index('county_id');
                $table->index('county_sequence');
                $table->index('status');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')
            ->dropIfExists('checkout_invoices');
    }
};