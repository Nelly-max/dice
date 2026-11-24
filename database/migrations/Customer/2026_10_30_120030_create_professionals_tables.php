<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Professionals (customer database): people who offer a service (make-up artist, plumber,
 * photographer …). A customer creates the profile in the Smart Market Hub; the admin hub approves
 * and activates it and can deactivate, suspend or ban it. Customers book them from their public
 * page (/pros/{account}), which they can link from TikTok, Instagram and so on.
 *
 *   professional_tariffs        what being listed costs (code TPR01 …, amount, billing cycle)
 *   professionals               the profile: who, what, where, account_status, comment from the team
 *   professional_links          social media / website links (platform + url)
 *   professional_subdivisions   markets the professional relates to (Glam Room for a make-up artist …)
 *   professional_services       what can be booked, with a price and how long it takes
 *   professional_jobs           bookings from customers ("jobs" for the professional)
 *   professional_subscriptions  the tariff periods: pending until paid, then active until ends_at
 *
 * Run (hub):  php artisan migrate --path=database/migrations/Customer/2026_11_19_220000_create_professionals_tables.php
 */
return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        $s = Schema::connection($this->connection);

        if (!$s->hasTable('professional_tariffs')) {
            $s->create('professional_tariffs', function (Blueprint $t) {
                $t->id();
                $t->string('code', 20)->unique();
                $t->string('name', 80);
                $t->decimal('amount', 12, 2);
                $t->string('billing_cycle', 20)->default('monthly');   // monthly | quarterly | yearly
                $t->unsignedInteger('max_services')->nullable();         // null = no limit
                $t->boolean('featured')->default(false);                  // shown first in the directory
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!$s->hasTable('professionals')) {
            $s->create('professionals', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('customer_id')->unique();
                $t->string('account', 20)->unique();                     // PRO0001
                $t->string('display_name', 80);
                $t->string('profession', 80);
                $t->string('category', 40)->nullable();
                $t->text('bio')->nullable();
                $t->string('phone', 20);
                $t->string('whatsapp', 20)->nullable();
                $t->string('email')->nullable();
                $t->unsignedBigInteger('county_id')->nullable();
                $t->unsignedBigInteger('town_id')->nullable();
                $t->string('area', 120)->nullable();
                $t->string('works_from', 20)->default('both');            // my_place | clients_place | both | online
                $t->decimal('rate_from', 12, 2)->nullable();
                $t->string('rate_unit', 20)->nullable();                   // job | hour | day | session
                $t->unsignedTinyInteger('years_experience')->nullable();
                $t->string('photo')->nullable();
                $t->unsignedBigInteger('tariff_id')->nullable();
                $t->string('account_status', 20)->default('pending');     // pending | active | rejected | deactivated | suspended | banned
                $t->text('comment')->nullable();                           // the team's note to the professional
                $t->timestamp('comment_at')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->string('approved_by')->nullable();
                $t->timestamp('status_changed_at')->nullable();
                $t->timestamps();

                $t->index('account_status');
                $t->index('category');
                $t->foreign('customer_id')->references('id')->on('customer_accounts')->cascadeOnDelete();
            });
        }

        if (!$s->hasTable('professional_links')) {
            $s->create('professional_links', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('professional_id')->index();
                $t->string('platform', 20);                                // tiktok | instagram | facebook | youtube | x | linkedin | website
                $t->string('url', 500);
                $t->timestamps();
            });
        }

        if (!$s->hasTable('professional_subdivisions')) {
            $s->create('professional_subdivisions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('professional_id');
                $t->unsignedBigInteger('sub_division_id')->index();
                $t->timestamps();
                $t->unique(['professional_id', 'sub_division_id'], 'professional_subdivision_unique');
            });
        }

        if (!$s->hasTable('professional_services')) {
            $s->create('professional_services', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('professional_id')->index();
                $t->string('name', 120);
                $t->string('description', 500)->nullable();
                $t->decimal('price', 12, 2)->nullable();
                $t->unsignedInteger('duration_minutes')->nullable();
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('sort')->default(0);
                $t->timestamps();
            });
        }

        if (!$s->hasTable('professional_jobs')) {
            $s->create('professional_jobs', function (Blueprint $t) {
                $t->id();
                $t->string('reference', 20)->unique();                     // JOB-7F3K2Q
                $t->unsignedBigInteger('professional_id')->index();
                $t->unsignedBigInteger('service_id')->nullable();
                $t->string('service_name', 120)->nullable();
                $t->unsignedBigInteger('customer_id')->nullable()->index();
                $t->string('customer_name', 80);
                $t->string('customer_phone', 20);
                $t->dateTime('wanted_at')->nullable();
                $t->string('location', 255)->nullable();
                $t->text('note')->nullable();
                $t->decimal('price', 12, 2)->nullable();
                $t->string('status', 20)->default('new');                  // new | accepted | declined | done | cancelled
                $t->string('source', 20)->nullable();                      // where the booking came from (?via=tiktok …)
                $t->string('reply', 500)->nullable();                      // the professional's message to the customer
                $t->timestamp('responded_at')->nullable();
                $t->timestamp('completed_at')->nullable();
                $t->timestamps();
                $t->index(['professional_id', 'status']);
            });
        }

        if (!$s->hasTable('professional_subscriptions')) {
            $s->create('professional_subscriptions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('professional_id')->index();
                $t->unsignedBigInteger('tariff_id');
                $t->decimal('amount', 12, 2);
                $t->string('status', 20)->default('pending');              // pending | active | expired | cancelled
                $t->timestamp('starts_at')->nullable();
                $t->timestamp('ends_at')->nullable();
                $t->string('payment_reference', 60)->nullable();
                $t->string('activated_by')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        $s = Schema::connection($this->connection);
        foreach (['professional_subscriptions', 'professional_jobs', 'professional_services', 'professional_subdivisions', 'professional_links', 'professionals', 'professional_tariffs'] as $t) {
            $s->dropIfExists($t);
        }
    }
};
