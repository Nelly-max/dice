<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MajorDivision extends Model
{
    protected $table = 'major_divisions';

    protected $fillable = ['name'];

    public $timestamps = false;

    public function subdivisions()
    {
        return $this->hasMany(Subdivision::class);
    }
}
