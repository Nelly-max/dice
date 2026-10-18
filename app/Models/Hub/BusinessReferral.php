<?php

namespace App\Models\Hub;

use Illuminate\Database\Eloquent\Model;

class BusinessReferral extends Model
{
    protected $connection = 'customer';

    protected $table = 'business_referrals';

    protected $fillable = [
        'marketer_id',
        'referral_code',
        'business_account',
        'discount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount' => 'decimal:2',
        ];
    }
}
