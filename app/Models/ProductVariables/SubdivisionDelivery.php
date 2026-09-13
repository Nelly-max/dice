<?php

namespace App\Models\ProductVariables;

use Illuminate\Database\Eloquent\Model;

class SubdivisionDelivery extends Model
{
    // Force the model to read from the product_variables database connection profile
    protected $connection = 'product_variables';

    // Target the table name exactly as specified in your migration layout
    protected $table = 'subdivision_delivery';

    protected $fillable = [
        'sub_division_id',
        'shipment_option',
    ];
}
