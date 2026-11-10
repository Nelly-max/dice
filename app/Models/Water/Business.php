<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Model;

/** A water shop (Water database, same `business` table shape as the other subdivisions). */
class Business extends Model
{
    protected $connection = 'water';

    protected $table = 'business';

    protected $guarded = [];
}
