<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Bedsitter, 1BR, 2BR
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_unit_types');
    }
};
