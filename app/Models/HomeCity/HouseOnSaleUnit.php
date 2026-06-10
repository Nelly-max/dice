<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseOnSaleUnit extends Model
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
        'property_id',
        'house_unit_type_id',
        'special_label',
        'unit_number',
        'price',
        'avg_monthly_rent',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'avg_monthly_rent' => 'decimal:2',
    ];

    /**
     * Get the property that owns the sale unit.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the specific house unit type (e.g., 3 Bedroom).
     */

    public function type()
    {
        return $this->belongsTo(HouseUnitType::class, 'house_unit_type_id');
    }

    /**
     * Get the images associated with this sale unit.
     */
    public function images(): HasMany
    {
        return $this->hasMany(HouseOnSaleImage::class, 'house_on_sale_unit_id');
    }
}
