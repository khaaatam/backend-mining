<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];
    protected $appends = ['expiry_status'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'stnk_expiry' => 'date',
        'kir_expiry' => 'date',
        'insurance_expiry' => 'date',
        'operating_hours' => 'decimal:1',
    ];

    // Konfigurasi Activity Log otomatis
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('vehicle_management');
    }

    public function getExpiryStatusAttribute(): string
    {
        $dates = [
            $this->stnk_expiry,
            $this->kir_expiry,
            $this->insurance_expiry
        ];

        $nearest = collect($dates)->filter()->min();
        if (!$nearest) return 'none';
        if ($nearest->isPast()) return 'expired';
        if ($nearest->diffInDays(now()) <= 30) return 'warning';
        return 'ok';
    }

    // Relationships
    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function currentOperator()
    {
        return $this->belongsTo(User::class, 'current_operator_id');
    }

    public function gpsProvider()
    {
        return $this->belongsTo(GpsProvider::class, 'gps_provider_id');
    }
}
