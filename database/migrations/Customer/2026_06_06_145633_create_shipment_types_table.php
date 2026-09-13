<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('customer')->create('shipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            
            // Financial value using decimal for precision (10 digits total, 2 decimals)
            $table->decimal('min_amount', 10, 2);
            
            // Delivery range boundaries (e.g., '1' to '3' days/hours)
            $table->integer('time1')->comment('Minimum delivery range boundary');
            $table->integer('time2')->comment('Maximum delivery range boundary');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('shipment_types');
    }
};
