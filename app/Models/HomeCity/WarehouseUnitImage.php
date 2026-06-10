<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseUnitImage extends Model
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
        'warehouse_unit_id',
        'image_path',
        'label',
    ];

    /**
     * Get the warehouse unit that owns the image.
     */
    public function warehouseUnit(): BelongsTo
    {
        return $this->belongsTo(WarehouseUnit::class, 'warehouse_unit_id');
    }
}
