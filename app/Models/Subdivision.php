<?php

namespace App\Models;

use App\Models\Customer\SubdivisionShipment; 

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
    
    public function connection()
    {
        return $this->db_connection;
    }
        
    public function shipment()
    {
        return $this->hasOne(SubdivisionShipment::class, 'subdivision_id');
    }
}
