<?php

namespace App\Models\ProductVariables;

use Illuminate\Database\Eloquent\Model;

class ProductPackaging extends Model
{
    protected $connection = 'product_variables';
    protected $table = 'product_packaging';
    protected $fillable = [
        'name', 
    ];
}
