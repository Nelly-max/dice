<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeUnit extends Model
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
        'unit_number',
        'measure_unit',
        'size',
        'price',
        'status',
        'remaining_space',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'decimal:2',
        'price' => 'decimal:2',
        'remaining_space' => 'decimal:2',
    ];

    /**
     * Get the property that owns the office unit.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
