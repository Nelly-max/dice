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
        Schema::connection('customer')->create('rider_personal_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rider_id')
                ->constrained('riders')
                ->cascadeOnDelete();

            $table->string('passport_photo')->nullable();

            $table->string('id_front_photo')->nullable();
            $table->string('id_back_photo')->nullable();

            $table->string('license_photo')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('rider_personal_details');
    }
};