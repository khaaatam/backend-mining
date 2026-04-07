<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorisasi di-handle oleh middleware route
    }

    public function rules(): array
    {
        // Ambil ID kendaraan dari route
        // Handle jika Laravel mengembalikan objek model atau sekadar string ID
        $vehicle = $this->route('vehicle');
        $vehicleId = is_object($vehicle) ? $vehicle->id : $vehicle;

        // Jika endpoint-nya khusus untuk update status (Quick Status Change)
        if ($this->routeIs('vehicles.status')) {
            return [
                'status' => 'required|string|in:active,idle,maintenance,breakdown,decommissioned'
            ];
        }

        // Rule untuk Create (POST) dan Full Update (PUT/PATCH)
        return [
            'asset_number' => 'required|string|unique:vehicles,asset_number,' . $vehicleId,
            'make' => 'required|string',
            'model' => 'required|string',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'status' => 'required|string|in:active,idle,maintenance,breakdown,decommissioned',
            'ownership_type' => 'required|string|in:owned,leased,rented',
            'vin' => 'nullable|string',
            'plate_number' => 'nullable|string',
            'year' => 'nullable|integer',
            'site_id' => 'nullable|integer',
            'current_operator_id' => 'nullable|exists:users,id',
            'operating_hours' => 'nullable|numeric',
            'gps_provider_id' => 'nullable|integer',
            'gps_device_id' => 'nullable|string',
            'stnk_expiry' => 'nullable|date',
            'kir_expiry' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'last_service_date' => 'nullable|date',
            'next_service_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
