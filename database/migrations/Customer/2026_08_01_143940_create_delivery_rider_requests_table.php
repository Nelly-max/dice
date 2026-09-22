<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::connection('customer')->create('delivery_rider_requests', function (Blueprint $table) {

            $table->id();


            /*
            |--------------------------------------------------------------------------
            | Request Reference
            |--------------------------------------------------------------------------
            */

            $table->string('request_number')
                ->unique();



            /*
            |--------------------------------------------------------------------------
            | Delivery & Rider
            |--------------------------------------------------------------------------
            */

            $table->foreignId('delivery_id')
                ->constrained('deliveries')
                ->cascadeOnDelete();


            $table->foreignId('rider_id')
                ->constrained('riders')
                ->cascadeOnDelete();



            /*
            |--------------------------------------------------------------------------
            | Attempt
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('attempt_number')
                ->default(1);



            /*
            |--------------------------------------------------------------------------
            | Request Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'pending',
                'accepted',
                'rejected',
                'expired'

            ])->default('pending');



            $table->string('rejected_reason')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Rider Trip Information
            |--------------------------------------------------------------------------
            */

            $table->decimal('distance_km', 8, 2)
                ->nullable();


            $table->integer('eta_minutes')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Request Timing
            |--------------------------------------------------------------------------
            */

            $table->timestamp('sent_at')
                ->useCurrent();


            // 10 second rider response window
            $table->timestamp('expires_at')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Rider Response
            |--------------------------------------------------------------------------
            */

            $table->timestamp('responded_at')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->timestamps();



            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'delivery_id',
                'status'
            ]);


            $table->index([
                'rider_id',
                'status'
            ]);


            $table->index('expires_at');


            $table->index('responded_at');

        });
    }



    public function down(): void
    {
        Schema::connection('customer')
            ->dropIfExists('delivery_rider_requests');
    }

};