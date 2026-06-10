<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialImage extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     */
    protected $connection = 'homecity';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'commercial_space_id',
        'property_id',
        'image_path',
        'label',
    ];

    /**
     * Get the commercial space that owns the image.
     */
    public function commercialSpace(): BelongsTo
    {
        return $this->belongsTo(CommercialSpace::class);
    }

    /**
     * Get the property that owns the image.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
