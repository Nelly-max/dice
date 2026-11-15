<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;

/** A message for a customer in the Hub (the bell): a comment on their application, an approval… */
class CustomerNotification extends Model
{
    protected $connection = 'customer';

    protected $table = 'customer_notifications';

    protected $fillable = ['customer_id', 'type', 'title', 'body', 'url', 'icon', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
