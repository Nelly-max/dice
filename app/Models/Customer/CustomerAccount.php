<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

// 2. CRITICAL: Extend Authenticatable instead of standard Model
class CustomerAccount extends Authenticatable 
{
    use HasFactory, SoftDeletes;

    // Force this model to use the 'customer' database connection configuration
    protected $connection = 'customer';

    // Explicitly map to your table name
    protected $table = 'customer_accounts';

    // Mass assignable fields matching your migration schema attributes
    protected $fillable = [
        'account',
        'uuid',
        'name',
        'email',
        'phone_number',
        'password',
        'gender',
        'status',
    ];

    // Hidden attributes for serialization safety layers
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function marketer()
    {
        return $this->hasOne(Marketer::class, 'customer_id');
    }

    /**
     * Eloquent Boot Model Hook
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }

            if (empty($customer->account)) {
                $customer->account = self::generateUniqueAccountNumber();
            }
        });
    }

    /**
     * Internal sequence routine checking database collision states
     */
    protected static function generateUniqueAccountNumber(): string
    {
        do {
            $accountNumber = 'ACC-' . mt_rand(10000000, 99999999);
            $exists = self::where('account', $accountNumber)->exists();
        } while ($exists);

        return $accountNumber;
    }
}
