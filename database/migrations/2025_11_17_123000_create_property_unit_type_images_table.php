<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_unit_type_images', function (Blueprint $table) {
            $table->id();

            // Link to property
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->cascadeOnDelete();

            // Link to unit type (optional, if image is for a specific unit type)
            $table->foreignId('house_unit_type_id')
                  ->nullable()
                  ->constrained('house_unit_types')
                  ->cascadeOnDelete();

            // Store image path
            $table->string('image_path');

            // Image label (optional)
            $table->string('label')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Optional
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_unit_type_images');
    }
};
