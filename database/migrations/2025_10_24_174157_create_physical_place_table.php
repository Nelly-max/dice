<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('physical_town_id');
            $table->timestamps();

            $table->foreign('physical_town_id')
                  ->references('id')
                  ->on('physical_towns')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_places');
    }
};
