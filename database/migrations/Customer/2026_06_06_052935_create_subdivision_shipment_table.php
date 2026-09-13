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
        Schema::connection('customer')->create('subdivision_shipment', function (Blueprint $table) {
            $table->unsignedBigInteger('subdivision_id');
            $table->string('consignment');
            
            // Primary key combination
            $table->primary(['subdivision_id', 'consignment']);
            
            // Cross-database foreign key linking to the hub database
            $table->foreign('subdivision_id')
                  ->references('id')
                  ->on('hub.sub_divisions') // Explicitly prefix the database name
                  ->onDelete('cascade'); // Deletes shipment records if the parent subdivision is deleted
                  
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('subdivision_shipment');
    }
};
