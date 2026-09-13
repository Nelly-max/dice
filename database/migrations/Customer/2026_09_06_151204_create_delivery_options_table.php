<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('customer')->create('delivery_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_division_id')->nullable();
            $table->enum('option', ['individual', 'general']);
            $table->timestamps();

            $table->unique(['sub_division_id', 'option']);

            // Cross-database foreign key constraint to the hub database
            $table->foreign('sub_division_id')
                  ->references('id')
                  ->on('hub.sub_divisions')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('delivery_options');
    }
};