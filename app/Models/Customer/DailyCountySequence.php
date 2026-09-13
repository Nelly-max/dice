<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use App\Models\PhysicalCounty;

class DailyCountySequence extends Model
{
    protected $connection = 'customer';

    protected $table = 'daily_county_sequence';

    protected $fillable = [
        'sequence_date',
        'county_id',
        'daily_code',
    ];

    protected $casts = [
        'sequence_date' => 'date',
    ];

    public function county()
    {
        return $this->belongsTo(PhysicalCounty::class, 'county_id');
    }
}