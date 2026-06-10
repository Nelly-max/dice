<?php

namespace App\Models\ProductVariables;

use Illuminate\Database\Eloquent\Model;

class QuantityUnit extends Model
{
    protected $connection = 'product_variables';
    protected $table = 'quantity_units';
    protected $fillable = [
        'product_id', 
        'code',
    ];
}
