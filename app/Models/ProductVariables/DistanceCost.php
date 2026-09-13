<?php

namespace App\Models\ProductVariables;

use Illuminate\Database\Eloquent\Model;

class DistanceCost extends Model
{
    protected $connection = 'product_variables';
    protected $table = 'distance_costs';

    protected $fillable = [
        'distance_from',
        'distance_to',
        'unit',
        'cost',
    ];
}
