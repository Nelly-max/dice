<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Model;

/** Picture of a company's container (water_company_id + water_container_id). */
class WaterCompanyImage extends Model
{
    protected $connection = 'water';

    protected $table = 'water_company_images';

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(WaterCompany::class, 'water_company_id');
    }
}
