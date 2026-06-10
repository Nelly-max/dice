<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lister extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'photo',
        'type',
        'open_day_start',
        'open_day_end',
        'opening_time',
        'closing_time',
    ];

    // A lister can have many properties
    public function properties()
    {
        return $this->hasMany(Property::class, 'lister_id');
    }
}
