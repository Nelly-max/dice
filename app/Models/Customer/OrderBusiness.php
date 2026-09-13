<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\SubDivision; 

class OrderBusiness extends Model
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
    protected $table = 'order_businesses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'business_account',
        'sub_division_id',
        'status',
        'accepted_at',
        'dispatched_at',
        'delivered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_id'        => 'integer',
        'sub_division_id' => 'integer',
        'accepted_at'     => 'datetime',
        'dispatched_at'   => 'datetime',
        'delivered_at'    => 'datetime',
    ];

    /**
     * Get the order that this business fulfillment record belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the sub-division details from across your separate hub database.
     * (Ensure your SubDivision model is configured to use the 'hub' database connection)
     */
    public function subDivision(): BelongsTo
    {
        return $this->belongsTo(SubDivision::class, 'sub_division_id');
    }

    /**
     * Helper scope to quickly filter business orders by specific status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
