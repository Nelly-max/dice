<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_referrals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('marketer_id');

            $table->string('referral_code');
            $table->string('business_account');

            $table->decimal('discount', 5, 2)->default(10.00);

            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index('marketer_id');
            $table->index('referral_code');
            $table->index('business_account');
            $table->index('status');

            $table->unique([
                'marketer_id',
                'business_account',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_referrals');
    }
};