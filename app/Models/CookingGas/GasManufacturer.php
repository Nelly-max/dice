<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GasManufacturer extends Model
{
    protected $connection = 'cookinggas';

    use HasFactory;

    protected $table = 'manufacturers';

    protected $fillable = ['name',];
}
