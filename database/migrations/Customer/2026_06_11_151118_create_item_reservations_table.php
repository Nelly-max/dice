<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('customer')->create('item_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained('cart')
                ->cascadeOnDelete();

            $table->string('stockable_type');
            $table->unsignedBigInteger('stockable_id');

            $table->unsignedInteger('quantity');

            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['stockable_type', 'stockable_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('item_reservations');
    }
};
