<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_number')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->enum('status', ['Vacant', 'Occupied', 'Booked'])->default('Vacant');

            $table->foreignId('house_unit_type_id')->constrained('house_unit_types')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_units');
    }
};
