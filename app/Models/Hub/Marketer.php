<?php

namespace App\Models\Hub;

use Illuminate\Database\Eloquent\Model;

use App\Models\Customer\CustomerAccount;

class Marketer extends Model
{
    protected $connection = 'customer';

    protected $fillable = [
        'customer_id',
        'name',
        'phone',
        'email',
        'gender',
        'date_of_birth',
        'mpesa_number',
        'referral_code',
        'discount_percentage',
        'url_link',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'discount_percentage' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_id');
    }
}
