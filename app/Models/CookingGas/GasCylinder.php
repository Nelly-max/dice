<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Model;

class GasCylinder extends Model
{
    // Use the cooking_gas database connection
    protected $connection = 'cookinggas';

    protected $fillable = [
        'brand_name',
        'manufacturer_id',
    ];

    // Relationships
    public function quantityImages()
    {
        return $this->hasMany(GasQntImage::class);
    }

    public function manufacturer()
    {
        return $this->belongsTo(GasManufacturer::class);
    }

    public function images()
    {
        return $this->hasMany(GasQntImage::class, 'gas_cylinder_id');
    }

    public function quantities()
    {
        return $this->belongsToMany(
            GasQuantity::class,       // related model
            'gas_qnt_images',         // pivot table
            'gas_cylinder_id',        // foreign key on pivot table pointing to this model
            'quantity_id'             // foreign key on pivot table pointing to related model
        )->withPivot('file_path');    // include the image path if needed
    }

    public function businessStock()
    {
        return $this->hasMany(
            BusinessGasStock::class,
            'gas_cylinder_id'
        );
    }
}
