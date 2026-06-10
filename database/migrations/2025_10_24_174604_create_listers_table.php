<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone'); // required
            $table->string('email')->unique();
            $table->string('photo');
            $table->enum('type', ['Company', 'Agency', 'Landlord']);

            // Open days
            $table->string('open_day_start')->nullable(); // e.g., Mon
            $table->string('open_day_end')->nullable();   // e.g., Fri

            // Operating hours
            $table->time('opening_time')->nullable();     // e.g., 08:00:00
            $table->time('closing_time')->nullable();     // e.g., 17:00:00

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listers');
    }
};
