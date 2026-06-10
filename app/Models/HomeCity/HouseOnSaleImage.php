<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseOnSaleImage extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'homecity';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'house_on_sale_unit_id',
        'property_id',
        'image_path',
        'label',
    ];

    /**
     * Get the sale unit that owns the image.
     */
    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(HouseOnSaleUnit::class, 'house_on_sale_unit_id');
    }

    /**
     * Get the property that owns the image.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
