<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Http\Requests\VehicleRequest;
use App\Http\Resources\VehicleResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Http\Request;


class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = QueryBuilder::for(Vehicle::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('vehicle_type_id'),
                AllowedFilter::exact('site_id'),
                AllowedFilter::exact('gps_provider_id'),
                'asset_number'
            ])
            ->allowedSorts([
                'asset_number',
                'operating_hours',
                'created_at'
            ])
            ->allowedIncludes([
                'vehicleType',
                'currentOperator',
                'gpsProvider'
            ])
            ->defaultSort('-created_at')
            ->paginate(request()->get('per_page', 15));

        return VehicleResource::collection($vehicles);
    }

    public function linkGps(Request $request, \App\Models\Vehicle $vehicle)
    {
        $validated = $request->validate([
            'gps_provider_id' => 'required|exists:gps_providers,id',
            'gps_device_id' => 'required|string|max:255|unique:vehicles,gps_device_id,' . $vehicle->id,
        ], [
            'gps_device_id.unique' => 'ID Perangkat / IMEI ini sudah terdaftar pada kendaraan lain.'
        ]);

        $vehicle->update($validated);

        return response()->json([
            'message' => 'GPS berhasil dipasang ke kendaraan',
            'data' => $vehicle->load('gpsProvider')
        ]);
    }

    public function store(VehicleRequest $request)
    {
        $vehicle = Vehicle::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Kendaraan berhasil ditambahkan.',
            'data' => new VehicleResource($vehicle)
        ], 201);
    }

    public function show($id)
    {
        $vehicle = QueryBuilder::for(Vehicle::class)
            ->allowedIncludes(['vehicleType', 'currentOperator', 'gpsProvider'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => new VehicleResource($vehicle)
        ]);
    }

    public function update(VehicleRequest $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data kendaraan berhasil diperbarui.',
            'data' => new VehicleResource($vehicle)
        ]);
    }

    public function updateStatus(VehicleRequest $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status kendaraan berhasil diubah menjadi ' . $request->status,
            'data' => new VehicleResource($vehicle)
        ]);
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kendaraan berhasil dihapus (soft delete).'
        ]);
    }
}
