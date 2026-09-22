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
        Schema::connection('customer')->create('rider_car_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rider_id')
                ->constrained('riders')
                ->cascadeOnDelete();

            $table->enum('vehicle_type', [
                'motorcycle',
                'car',
                'bicycle',
                'van',
                'truck'
            ]);

            $table->string('vehicle_make');
            $table->string('vehicle_model');
            $table->string('plate_number')->unique();

            $table->string('logbook_photo')->nullable();

            $table->string('front_photo')->nullable();
            $table->string('side_photo')->nullable();
            $table->string('back_photo')->nullable();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('rider_car_details');
    }
};