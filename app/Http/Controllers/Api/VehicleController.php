<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = QueryBuilder::for(Vehicle::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('vehicle_type_id'),
                'asset_number',
                'plate_number'
            ])
            ->allowedSorts(['asset_number', 'operating_hours', 'created_at'])
            ->allowedIncludes(['vehicleType', 'currentOperator'])
            ->defaultSort('-created_at')
            ->paginate(request()->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_number' => 'required|string|unique:vehicles,asset_number',
            'make' => 'required|string',
            'model' => 'required|string',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'status' => 'required|in:active,idle,maintenance,breakdown,decommissioned',
            'ownership_type' => 'required|in:owned,leased,rented',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kendaraan berhasil ditambahkan.',
            'data' => $vehicle
        ], 201);
    }

    public function show($id)
    {
        $vehicle = Vehicle::with(['vehicleType', 'currentOperator'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $vehicle
        ]);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'asset_number' => 'required|string|unique:vehicles,asset_number,' . $id,
            'make' => 'required|string',
            'model' => 'required|string',
            'status' => 'required|in:active,idle,maintenance,breakdown,decommissioned',
        ]);

        $vehicle->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data kendaraan berhasil diperbarui.',
            'data' => $vehicle
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,idle,maintenance,breakdown,decommissioned'
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status kendaraan berhasil diubah menjadi ' . $request->status,
            'data' => $vehicle
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
