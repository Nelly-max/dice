<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('property_bills', function (Blueprint $table) {
            $table->id();

            // FK → properties
            $table->unsignedBigInteger('property_id');

            // FK → bills table (water, electricity, etc.)
            $table->unsignedBigInteger('bill_id');

            // FK → bill_types table (meter, tokens, shared, etc.)
            $table->unsignedBigInteger('bill_type_id');

            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->foreign('bill_type_id')->references('id')->on('bill_types')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_bills');
    }

};
