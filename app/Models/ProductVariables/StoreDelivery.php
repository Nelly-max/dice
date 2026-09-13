<?php

namespace App\Models\ProductVariables;

use Illuminate\Database\Eloquent\Model;

class StoreDelivery extends Model
{
    // Force the model to read from the product_variables database connection profile
    protected $connection = 'product_variables';

    // Target the table name exactly as specified in your migration layout
    protected $table = 'store_delivery';

    protected $fillable = [
        'sub_division_id',
        'business_account',
        'delivery_charge',
        'charge_at',
    ];

    /**
     * Helper mutator to ensure clean typecasting
     */
    protected $casts = [
        'delivery_charge' => 'boolean',
        'charge_at'       => 'decimal:2', // Keeps precision up to 2 decimal points (e.g., 3.50 km)
    ];
}
