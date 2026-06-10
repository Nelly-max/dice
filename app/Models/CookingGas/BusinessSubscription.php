<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSubscription extends Model
{
    use HasFactory;

    protected $connection = 'cookinggas';

    protected $table = 'business_subscription';

    protected $fillable = [
        'business_id',
        'tariff_id',
        'status',
        'activated_at',
        'expires_at',
    ];

    
    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];



    public function tariff() {
        return $this->belongsTo(Tariff::class, 'tariff_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

}
