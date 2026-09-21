<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('customer')->create('customer_accounts', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identification & Authentication
            |--------------------------------------------------------------------------
            */
            $table->string('account')
                ->unique()
                ->comment('Unique customer account number');

            $table->uuid('uuid')
                ->unique()
                ->comment('Public unique identifier for API masking');

            $table->string('name')
                ->comment('Full name or display name of the customer');

            $table->string('username')
                ->unique()
                ->nullable()
                ->comment('Unique customer username');

            $table->string('email')
                ->unique()
                ->comment('Primary contact and login email');

            $table->string('phone_number')
                ->nullable()
                ->comment('E.164 formatted phone number');

            $table->string('password')
                ->comment('Hashed authentication password');

            /*
            |--------------------------------------------------------------------------
            | Demographics & Profile
            |--------------------------------------------------------------------------
            */
            $table->string('national_id')
                ->nullable()
                ->index()
                ->comment('Customer national identification number');

            $table->string('gender', 30)
                ->nullable()
                ->comment('Customer gender designation');

            $table->date('date_of_birth')
                ->nullable()
                ->comment('Customer date of birth');

            $table->string('profile_image')
                ->nullable()
                ->comment('Relative path or URL to the customer profile image');

            /*
            |--------------------------------------------------------------------------
            | Account Status & Verification
            |--------------------------------------------------------------------------
            */
            $table->string('status')
                ->default('active')
                ->comment('Account state: active, suspended, pending');

            $table->timestamp('email_verified_at')
                ->nullable()
                ->comment('Timestamp when email was verified');

            $table->timestamp('phone_verified_at')
                ->nullable()
                ->comment('Timestamp when phone number was verified');

            // Required for Laravel's "Remember Me" authentication
            $table->rememberToken();

            /*
            |--------------------------------------------------------------------------
            | System Tracking
            |--------------------------------------------------------------------------
            */
            $table->timestamps();

            $table->softDeletes()
                ->comment('Enables safe restoration of deleted customer profiles');

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->index('account');
            $table->index('gender');
            $table->index('status');
            $table->index('phone_number');
            $table->index(['email', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Apply table comment for MySQL engines
        if (DB::connection('customer')->getDriverName() === 'mysql') {
            DB::connection('customer')->statement(
                "ALTER TABLE customer_accounts COMMENT = 'Core customer profiles and authentication credentials'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('customer_accounts');
    }
};

