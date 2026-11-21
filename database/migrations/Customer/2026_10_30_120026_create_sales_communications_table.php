<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('customer')->create('sales_communications', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->unsignedTinyInteger('age');
            $table->string('gender')->nullable();
            $table->string('county')->nullable();
            $table->string('town')->nullable();
            $table->text('comment')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('sales_communications');
    }
};