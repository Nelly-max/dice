<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');

            // listing type options
            $table->enum('listing_type', ['forRent', 'onLease', 'onSale']);

            // commercial vs residential
            $table->enum('use_type', ['commercial', 'residential', 'both'])
                ->default('residential');

            $table->longText('description')->nullable();

            // Foreign keys
            $table->unsignedBigInteger('property_category_id');
            $table->unsignedBigInteger('house_property_type_id');
            $table->unsignedBigInteger('lister_id');  // property owner / company

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('property_category_id')
                ->references('id')
                ->on('property_categories')
                ->onDelete('cascade');

            $table->foreign('house_property_type_id')
                ->references('id')
                ->on('house_property_types')
                ->onDelete('cascade');

            $table->foreign('lister_id')
                ->references('id')
                ->on('listers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
