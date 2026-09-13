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
        Schema::connection('customer')->create('rider_applications', function (Blueprint $table) {
            $table->id();


            // Identification
            $table->string('national_id', 30)->unique();
            $table->string('license_number')->nullable()->unique();

            // Applicant Details
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email')->unique();

            // Application Status
            $table->enum('approval_status', [
                'processed',
                'approved',
                'corrections',
                'rejected',
            ])->default('processed');

            $table->text('remarks')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('rider_applications');
    }
};