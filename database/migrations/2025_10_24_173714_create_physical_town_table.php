<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_towns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('physical_county_id');
            $table->timestamps();

            $table->foreign('physical_county_id')
                  ->references('id')
                  ->on('physical_counties')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_towns');
    }
};
