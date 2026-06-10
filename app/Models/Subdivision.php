<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubDivision extends Model
{
    // 🔑 IMPORTANT: correct DB connection
    protected $connection = 'hub';

    protected $table = 'sub_divisions';

    protected $fillable = [
        'major_division_id',
        'name',
        'db_connection',
        'status',
        'logo',
    ];

    // ✅ timestamps EXIST in migration
    public $timestamps = true;

    public function majorDivision()
    {
        return $this->belongsTo(MajorDivision::class);
    }
}
