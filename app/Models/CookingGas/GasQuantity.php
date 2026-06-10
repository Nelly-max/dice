<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GasQuantity extends Model
{
    protected $connection = 'cookinggas';

    use HasFactory;

    // The column(s) that are mass assignable
    protected $fillable = ['quantity'];

    public function cylinders()
    {
        return $this->belongsToMany(
            GasCylinder::class,
            'gas_qnt_images',
            'quantity_id',
            'gas_cylinder_id'
        )->withPivot('file_path');
    }
}
