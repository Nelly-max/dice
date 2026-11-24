<?php

namespace App\Models\Hub;

use App\Models\Customer\CustomerAccount;
use Illuminate\Database\Eloquent\Model;

/**
 * A professional's profile (customer.professionals; created by the admin hub's
 * 2026_11_19 Customer migration). See App\Support\Professionals for the lists.
 */
class Professional extends Model
{
    protected $connection = 'customer';

    protected $table = 'professionals';

    protected $guarded = ['id'];

    protected $casts = [
        'rate_from' => 'decimal:2',
        'comment_at' => 'datetime',
        'approved_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_id');
    }

    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    /** the signed-in customer's profile, or null (also when the table isn't there yet) */
    public static function mine(): ?self
    {
        $customer = auth('customer')->user();
        if (!$customer) return null;
        return rescue(fn () => static::where('customer_id', $customer->id)->first(), null, false);
    }
}
