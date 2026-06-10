<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'bill_id',
        'bill_type_id',
    ];

    /* ============================================
     |  RELATIONSHIPS
     * ============================================ */

    /** Property */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /** Bill (water, electricity, etc.) */
    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    /** Bill Type (meter, tokens, shared, etc.) */
    public function billType()
    {
        return $this->belongsTo(BillType::class, 'bill_type_id');
    }
}
