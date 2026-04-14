<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class GpsPing extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'recorded_at',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'altitude',
        'raw_payload',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude'    => 'float',
        'longitude'   => 'float',
        'speed'       => 'float',
        'heading'     => 'float',
        'altitude'    => 'float',
        'raw_payload' => 'array',
    ];

    protected static function booted(): void
    {
        // After inserting a new ping, update the PostGIS geometry column.
        // Eloquent can't express ST_MakePoint in fillable, so we do it
        // with a follow-up raw statement on the created event.
        static::created(function (GpsPing $ping) {
            DB::statement("
                UPDATE gps_pings
                SET coordinates = ST_SetSRID(ST_MakePoint(?, ?), 4326)
                WHERE id = ?
            ", [$ping->longitude, $ping->latitude, $ping->id]);
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Scope: pings for a vehicle within a time range.
     * Automatically targets the correct monthly partition.
     */
    public function scopeForVehicleInRange(
        $query,
        int $vehicleId,
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
    ) {
        return $query
            ->where('vehicle_id', $vehicleId)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at');
    }
}
