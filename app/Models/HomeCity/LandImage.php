<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandImage extends Model
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
        'land_measure_unit_id',
        'image_path',
        'label',
    ];

    /**
     * Get the property that owns the land image.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the measure unit associated with the land image.
     */
    public function measureUnit(): BelongsTo
    {
        return $this->belongsTo(LandMeasureUnit::class, 'land_measure_unit_id');
    }
}
