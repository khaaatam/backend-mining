<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    // Menambahkan custom attribute agar selalu terbaca di response JSON
    protected $appends = ['expiry_status'];

    // Mengubah string tanggal menjadi instance Carbon
    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'stnk_expiry' => 'date',
        'kir_expiry' => 'date',
        'insurance_expiry' => 'date',
    ];

    public function getExpiryStatusAttribute(): string
    {
        $dates = [
            $this->stnk_expiry,
            $this->kir_expiry,
            $this->insurance_expiry
        ];

        $nearest = collect($dates)->filter()->min();

        if (!$nearest) {
            return 'none';
        }

        if ($nearest->isPast()) {
            return 'expired'; // Merah
        }

        if ($nearest->diffInDays(now()) <= 30) {
            return 'warning'; // Kuning
        }

        return 'ok'; // Hijau
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function currentOperator()
    {
        return $this->belongsTo(User::class, 'current_operator_id');
    }
}
