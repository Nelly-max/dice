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
        Schema::connection('customer')->create('rider_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rider_id')
                ->constrained('riders')
                ->cascadeOnDelete();

            // Current GPS Location
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Rider availability
            $table->enum('status', [
                'online',
                'offline',
                'busy'
            ])->default('offline');

            // Optional telemetry
            $table->decimal('heading', 5, 2)->nullable(); // Direction (degrees)
            $table->decimal('speed', 6, 2)->nullable();   // km/h
            $table->decimal('accuracy', 6, 2)->nullable(); // GPS accuracy in metres

            // Last GPS update
            $table->timestamp('last_seen')->useCurrent();

            $table->timestamps();

            $table->unique('rider_id');
            $table->index(['status', 'last_seen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('rider_locations');
    }
};