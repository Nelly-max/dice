<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialSpace extends Model
{
    use HasFactory;

    protected $connection = 'homecity';

    protected $table = 'commercial_spaces';

    protected $fillable = [
        'property_id',
        'space_type',
        'measure_unit',
        'total_space',
        'price_per_sqr',
        'total_units',
        'unit_price',
        'rental_mode',
    ];

    protected $casts = [
        'total_space' => 'decimal:2',
        'price_per_sqr' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_units' => 'integer',
    ];

    /*

    |---------------------------------------------------------
    | Relationships
    |---------------------------------------------------------
    */

    /**
     * The commercial space belongs to a main Property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /*

    |---------------------------------------------------------
    | Helpers
    |---------------------------------------------------------
    */

    /**
     * Determine the price based on the rental mode.
     */
    public function getEffectivePriceAttribute()
    {
        return $this->rental_mode === 'by_unit' 
            ? $this->unit_price 
            : $this->price_per_sqr;
    }
    public function images()
    {
        return $this->hasMany(CommercialImage::class, 'commercial_space_id');
    }
}
