<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_extra_charges', function (Blueprint $table) {
            $table->id();

            // FK → properties
            $table->unsignedBigInteger('property_id');

            // FK → extra_charges
            $table->unsignedBigInteger('extra_charge_id');

            $table->decimal('amount', 10, 2)->nullable();

            $table->timestamps();

            $table->foreign('property_id')
                  ->references('id')->on('properties')
                  ->onDelete('cascade');

            $table->foreign('extra_charge_id')
                  ->references('id')->on('extra_charges')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_extra_charges');
    }
};
