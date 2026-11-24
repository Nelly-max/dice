<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads: businesses that could join Smart Market (Sales → Leads in the hub).
 *
 *  business_leads           the business, one or two contact numbers, where it is, how far along it is
 *  business_lead_messages   every SMS sent to a lead (from a template or typed), with the result
 *
 * status: new → contacted → interested → onboarded   (or not_interested)
 * Location ids point at the hub's physical_counties / physical_towns / physical_places.
 *
 * Run:  php artisan migrate --path=database/migrations/Customer/2026_11_12_230000_create_business_leads_tables.php
 */
return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        $schema = Schema::connection('customer');

        if (!$schema->hasTable('business_leads')) {
            $schema->create('business_leads', function (Blueprint $table) {
                $table->id();
                $table->string('name');                                   // business name
                $table->string('contact_name')->nullable();               // person to talk to
                $table->string('phone', 20);
                $table->string('phone_2', 20)->nullable();
                $table->string('email')->nullable();
                $table->unsignedBigInteger('sub_division_id')->nullable(); // market it would join (hub.sub_divisions)
                $table->string('business_type')->nullable();               // e.g. shop, supermarket, salon
                $table->unsignedBigInteger('county_id')->nullable();
                $table->unsignedBigInteger('town_id')->nullable();
                $table->unsignedBigInteger('place_id')->nullable();
                $table->string('address')->nullable();                     // street / building / landmark
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('status', 30)->default('new');
                $table->string('source')->nullable();                      // walk-in, referral, phone, ...
                $table->text('notes')->nullable();
                $table->unsignedInteger('messages_sent')->default(0);
                $table->timestamp('last_contacted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();      // hub user
                $table->unsignedBigInteger('marketer_id')->nullable();
                $table->timestamps();

                $table->index('phone');
                $table->index('status');
                $table->index('sub_division_id');
                $table->index('marketer_id');
                $table->index(['county_id', 'town_id']);
            });
        }

        if (!$schema->hasTable('business_lead_messages')) {
            $schema->create('business_lead_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_lead_id')->constrained('business_leads')->cascadeOnDelete();
                $table->string('channel', 20)->default('sms');
                $table->string('phone', 20);
                $table->unsignedBigInteger('template_id')->nullable();     // hub notification_templates
                $table->text('message');
                $table->string('status', 20);                              // sent | failed
                $table->string('error')->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();         // hub user
                $table->timestamps();

                $table->index(['business_lead_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('business_lead_messages');
        Schema::connection('customer')->dropIfExists('business_leads');
    }
};
