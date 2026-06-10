<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelUnit extends Model
{
    use HasFactory;

    protected $connection = 'homecity';

    protected $table = 'hostel_units';

    protected $fillable = [
        'property_id',
        'room_number',
    ];

    /*

    |---------------------------------------------------------
    | Relationships
    |---------------------------------------------------------
    */

    /**
     * The room belongs to a main Property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    
    public function images()
    {
        return $this->hasMany(HostelImage::class, 'hostel_id');
    }
}
