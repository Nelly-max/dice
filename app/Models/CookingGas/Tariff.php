<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    use HasFactory;

    protected $connection = 'cookinggas';
    protected $fillable = [
        'code',
        'name',
        'description',
        'amount',
    ];
}
