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
        // Cek apakah ini proses update (PUT/PATCH) atau create (POST)
        $vehicleId = $this->route('vehicle') ? $this->route('vehicle')->id : null;

        $rules = [
            'asset_number' => 'required|string|unique:vehicles,asset_number,' . $vehicleId,
            'make' => 'required|string',
            'model' => 'required|string',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'status' => 'required|in:active,idle,maintenance,breakdown,decommissioned',
            'ownership_type' => 'required|in:owned,leased,rented',
            'vin' => 'nullable|string',
            'plate_number' => 'nullable|string',
            'year' => 'nullable|integer',
            'site_id' => 'nullable|integer',
            'current_operator_id' => 'nullable|exists:users,id',
            'operating_hours' => 'nullable|numeric',
        ];

        // Jika endpoint-nya hanya patch status, kita perkecil rule-nya
        if ($this->isMethod('patch') && $this->routeIs('vehicles.status')) {
            return [
                'status' => 'required|in:active,idle,maintenance,breakdown,decommissioned'
            ];
        }

        return $rules;
    }
}
