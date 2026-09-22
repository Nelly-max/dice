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
        Schema::connection('customer')->create('marketers', function (Blueprint $table) {
            $table->id();

             $table->unsignedBigInteger('customer_id');

            $table->foreign('customer_id')
                ->references('id')
                ->on('customer_accounts')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();

            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('mpesa_number')->nullable();

            $table->string('referral_code')->unique();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('url_link')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketers');
    }
};
