<?php


namespace App\Models\HomeMarket;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hub\Category; // Example external model
use App\Models\DimensionPreset;
use App\Models\MaterialType;
use App\Models\SpecialRequirement;
use App\Models\ProductVariables\QuantityUnit;
use App\Models\ProductVariables\ProductPackaging;

class ProductItem extends Model
{
    protected $connection = 'homemarket';
    protected $table = 'product_items';
    protected $fillable = [
        'product_id', 'code', 'dimension_id', 'material_type_id',
        'special_requirement_id', 'weight_value', 'weight_unit_id',
        'size_value', 'quantity_unit_id', 'packaging_id', 'pieces', 'barcode'
    ];

    // Relations to products
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function quantityUnit()
    {
        return $this->belongsTo(QuantityUnit::class);
    }

    public function packaging()
    {
        return $this->belongsTo(ProductPackaging::class);
    }

    public function images()
    {
        return $this->hasMany(
            ProductItemImage::class,
            'product_item_id'
        );
    }
}
