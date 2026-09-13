<?php

namespace App\Models\Hub;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderLocation extends Model
{
    use HasFactory;

    /**
     * The database connection that should be used by the model.
     * Matches the schema configuration 'customer'.
     *
     * @var string
     */
    protected $connection = 'customer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rider_id',
        'latitude',
        'longitude',
        'status',
        'heading',
        'speed',
        'accuracy',
        'last_seen',
    ];

    /**
     * The attributes that should be cast.
     * Ensures floats and dates are typed properly instead of returned as strings.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'heading' => 'float',
        'speed' => 'float',
        'accuracy' => 'float',
        'last_seen' => 'datetime',
    ];

    /**
     * Get the rider that owns this telemetry location snapshot.
     * Assumes Rider model lives in App\Models\Rider and uses the same or default connection.
     *
     * @return BelongsTo
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, 'rider_id');
    }

    /**
     * Scope a query to only include online riders.
     * Useful for live map querying filters.
     */
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    /**
     * Scope a query to filter out stale data.
     * Automatically filters out riders who haven't updated in the last X minutes.
     */
    public function scopeActiveRecently($query, int $minutes = 5)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));
    }
}
