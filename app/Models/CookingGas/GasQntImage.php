<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Model;

class GasQntImage extends Model
{
    protected $connection = 'cookinggas';

    protected $fillable = [
        'gas_cylinder_id',
        'quantity_id',
        'file_path',
    ];

    public function cylinder()
    {
        return $this->belongsTo(GasCylinder::class, 'gas_cylinder_id');
    }
    
    public function quantity()
    {
        return $this->belongsTo(GasQuantity::class, 'quantity_id');
    }
}
