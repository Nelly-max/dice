<?php

namespace App\Models\HomeMarket;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'homemarket';
    protected $table = 'products';

    protected $fillable = [
        'code',
        'name',
        'brand',
        'description',
        'category_id',
        'subcategory_id',
        'generic_id',
        'manufacturer_id',
    ];

    // PRODUCT ITEMS
    public function items()
    {
        return $this->hasMany(ProductItem::class);
    }

    // RELATIONSHIPS TO OTHER MODELS
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function generic()
    {
        return $this->belongsTo(GenericName::class, 'generic_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }
}
