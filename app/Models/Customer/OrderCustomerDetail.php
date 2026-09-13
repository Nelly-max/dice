<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCustomerDetail extends Model
{
    /**
     * The database connection that should be used by the model.
     * Explicitly bound to your 'customer' database connection.
     *
     * @var string
     */
    protected $connection = 'customer';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_customer_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'customer_id',
        'name',
        'email',
        'phone_number',
        'alternative_phone_number',
        'delivery_location',
        'latitude',
        'longitude',
        'delivery_note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_id'    => 'integer',
        'customer_id' => 'integer',
        'latitude'    => 'float',
        'longitude'   => 'float',
    ];

    /**
     * Get the order that this customer detail snapshot belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the master customer account associated with this record.
     */
    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_id');
    }
}
