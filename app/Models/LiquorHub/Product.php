<?php

namespace App\Models\LiquorHub;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'liquorhub';
    protected $table = 'products';

    protected $casts = [
        'alcohol_percentage' => 'decimal:2',
    ];

    protected $fillable = [
        'code',
        'name',
        'brand',
        'description',
        'category_id',
        'subcategory_id',
        'generic_id',
        'manufacturer_id',
        'alcohol_percentage',
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

    /*
    |--------------------------------------------------------------------------
    | Alcohol by volume
    |--------------------------------------------------------------------------
    | alcohol_percentage is stored as a decimal (e.g. 40.00). abv_label gives
    | "40% ABV" / "4.5% ABV", or null when it has not been captured yet.
    */
    public function getAbvLabelAttribute(): ?string
    {
        if ($this->alcohol_percentage === null || $this->alcohol_percentage === '') {
            return null;
        }

        $value = rtrim(rtrim(number_format((float) $this->alcohol_percentage, 2, '.', ''), '0'), '.');

        return $value . '% ABV';
    }
}
