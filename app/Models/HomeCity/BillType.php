<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillType extends Model
{
    protected $fillable = [
        'bill_id',
        'type'
    ];

    public function bill()
    {
        return $this->belongsTo(PropertyBill::class);
    }
}
