<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookings for Airbnb (stays) and Home City (viewings), on the customer database so the
 * three apps share them:
 *   customer app  → a customer requests a stay / a viewing, sees and cancels it
 *   business app  → the host / lister confirms, declines, checks out
 *   hub           → Sales → Bookings, everything across all businesses
 *
 * Run once, from the hub:  php artisan migrate --database=customer --path=database/migrations/Customer
 */
return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        if (!Schema::connection('customer')->hasTable('bookings')) {
            Schema::connection('customer')->create('bookings', function (Blueprint $t) {
                $t->id();
                $t->string('reference', 20)->unique();                 // BK-7Q2M9X
                $t->string('subdivision', 20)->index();                // airbnb | homecity
                $t->string('kind', 20)->default('stay');               // stay | viewing

                // what is booked (ids in the airbnb / homecity database)
                $t->unsignedBigInteger('listing_id')->nullable();
                $t->unsignedBigInteger('property_id')->nullable()->index();   // airbnb house / homecity property
                $t->string('unit_key', 60)->nullable();                // airbnb unit type id, homecity unit group key
                $t->string('unit_label', 120)->nullable();             // "2 Bedroom", "Office · 400 sqft"
                $t->string('unit_number', 60)->nullable();             // the unit given, once known
                $t->string('property_title')->nullable();

                // who it is with
                $t->string('business_account', 60)->nullable()->index();   // listing lister_id
                $t->string('business_name')->nullable();
                $t->string('business_phone', 30)->nullable();

                // who booked
                $t->unsignedBigInteger('customer_id')->nullable()->index();
                $t->string('customer_name');
                $t->string('customer_phone', 30)->index();
                $t->string('customer_email')->nullable();

                // when
                $t->date('check_in')->nullable();
                $t->date('check_out')->nullable();
                $t->dateTime('viewing_at')->nullable();
                $t->unsignedSmallInteger('guests')->default(1);
                $t->unsignedSmallInteger('nights')->default(0);

                // money (KES)
                $t->decimal('unit_price', 12, 2)->default(0);
                $t->decimal('cleaning_fee', 12, 2)->default(0);
                $t->decimal('service_fee', 12, 2)->default(0);
                $t->decimal('amount', 12, 2)->default(0);
                $t->decimal('paid_amount', 12, 2)->default(0);
                $t->string('payment_status', 20)->default('unpaid');   // unpaid | partial | paid | refunded

                // state
                $t->string('status', 20)->default('pending')->index(); // pending | confirmed | declined | cancelled | completed | no_show
                $t->text('notes')->nullable();                         // from the customer
                $t->text('business_note')->nullable();                 // from the host / lister / hub
                $t->string('cancel_reason')->nullable();
                $t->string('cancelled_by', 20)->nullable();            // customer | business | hub
                $t->timestamp('confirmed_at')->nullable();
                $t->timestamp('cancelled_at')->nullable();
                $t->timestamp('completed_at')->nullable();
                $t->timestamps();

                $t->index(['subdivision', 'status']);
                $t->index(['property_id', 'unit_key', 'check_in', 'check_out'], 'bookings_dates_index');
            });
        }

        if (!Schema::connection('customer')->hasTable('booking_events')) {
            Schema::connection('customer')->create('booking_events', function (Blueprint $t) {
                $t->id();
                $t->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $t->string('action', 30);                              // requested | confirmed | declined | cancelled | completed | no_show | paid | note | sms
                $t->string('by_type', 20)->nullable();                 // customer | business | hub
                $t->string('by_id', 60)->nullable();
                $t->string('by_name')->nullable();
                $t->text('note')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('booking_events');
        Schema::connection('customer')->dropIfExists('bookings');
    }
};
