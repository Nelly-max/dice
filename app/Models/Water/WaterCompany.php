<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Model;

/** A water brand / company (Keringet, Dasani, a local purifier...). */
class WaterCompany extends Model
{
    protected $connection = 'water';

    protected $table = 'water_companies';

    protected $guarded = [];
}
