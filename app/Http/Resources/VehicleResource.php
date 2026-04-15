<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_number' => $this->asset_number,
            'plate_number' => $this->plate_number,
            'status' => $this->status,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'vin' => $this->vin,
            'ownership_type' => $this->ownership_type,
            'operating_hours' => $this->operating_hours,
            'stnk_expiry' => $this->stnk_expiry,
            'kir_expiry' => $this->kir_expiry,
            'insurance_expiry' => $this->insurance_expiry,
            'last_service_date' => $this->last_service_date,
            'next_service_date' => $this->next_service_date,

            // Kolom krusial buat modul M04 [cite: 84]
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'gps_provider_id' => $this->gps_provider_id,
            'gps_device_id' => $this->gps_device_id,

            // Relasi (Gunakan whenLoaded agar tidak error jika tidak di-include)
            'vehicle_type' => new VehicleTypeResource($this->whenLoaded('vehicleType')),
            'gps_provider' => new GpsProviderResource($this->whenLoaded('gpsProvider')),
            'current_operator' => $this->whenLoaded('currentOperator'),

            // Log Aktivitas dari Spatie
            'activities' => $this->whenLoaded('activities', function () {
                return $this->activities()->latest()->take(10)->get();
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at,
        ];
    }
}
