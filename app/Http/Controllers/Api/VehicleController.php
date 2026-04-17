<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Http\Requests\VehicleRequest;
use App\Http\Resources\VehicleResource;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = QueryBuilder::for(Vehicle::class)
            ->with(['vehicleType', 'currentOperator', 'gpsProvider'])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('vehicle_type_id'),
                AllowedFilter::exact('site_id'),
                AllowedFilter::exact('gps_provider_id'),
                'asset_number'
            ])
            ->allowedSorts(['asset_number', 'operating_hours', 'created_at'])
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
            ->allowedIncludes(['vehicleType', 'currentOperator', 'gpsProvider', 'activities'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => new VehicleResource($vehicle)
        ]);
    }

    public function activities($id)
    {
        $vehicle = Vehicle::findOrFail($id);

        // Ambil log aktivitas dari Spatie
        $activities = $vehicle->activities()
            ->with('causer')
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $activities
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

    public function tracking(\App\Models\Vehicle $vehicle)
    {
        if (!$vehicle->gps_device_id) {
            return response()->json([
                'result' => -1,
                'status' => [],
                'error' => 'GPS device belum di-set'
            ], 400);
        }

        try {
            // ambil provider
            $provider = $vehicle->gpsProvider;

            if (!$provider) {
                return response()->json([
                    'result' => -1,
                    'status' => [],
                    'error' => 'Provider tidak ditemukan'
                ], 400);
            }

            if (!$provider->is_active) {
                return response()->json([
                    'result' => -1,
                    'status' => [],
                    'error' => 'Provider tidak aktif'
                ], 400);
            }

            // build URL
            $baseUrl = rtrim($provider->base_url, '/');
            $endpoint = ltrim($provider->location_endpoint ?? '/device-status', '/');
            $url = $baseUrl . '/' . $endpoint;

            // auth config
            $authConfig = $provider->auth_config ?? [];
            $headers = [];

            if ($provider->auth_type === 'bearer') {
                $headers['Authorization'] = 'Bearer ' . ($authConfig['key'] ?? '');
            } elseif ($provider->auth_type === 'api_key') {
                $headers[$authConfig['header'] ?? 'X-API-KEY'] = $authConfig['key'] ?? '';
            }

            // request ke provider
            $response = Http::withHeaders($headers)
                ->get($url, [
                    'devices' => $vehicle->gps_device_id
                ]);

            // 🔥 DEBUG RESPONSE (sementara)
            if (!$response->successful()) {
                return response()->json([
                    'result' => -1,
                    'status' => [],
                    'error' => 'Request ke provider gagal',
                    'debug' => [
                        'url' => $url,
                        'headers' => $headers,
                        'response' => $response->body()
                    ]
                ], 500);
            }

            $data = $response->json();

            // parsing
            $deviceData = $data['results'][0]['data'] ?? null;

            if (!$deviceData || $deviceData['result'] !== 0) {
                return response()->json([
                    'result' => -1,
                    'status' => [],
                    'error' => 'Data GPS tidak valid',
                    'debug' => $data
                ], 404);
            }

            $status = $deviceData['status'][0] ?? null;

            if (!$status) {
                return response()->json([
                    'result' => -1,
                    'status' => [],
                    'error' => 'Status kosong',
                    'debug' => $deviceData
                ], 404);
            }

            // ✅ FINAL OUTPUT SESUAI PDF
            return response()->json([
                'result' => $deviceData['result'],
                'status' => [
                    [
                        'mlat' => $status['mlat'] ?? null,
                        'mlng' => $status['mlng'] ?? null,
                        'sp'   => $status['sp'] ?? 0,
                        'hx'   => $status['hx'] ?? null,
                        'gt'   => $status['gt'] ?? null,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => -1,
                'status' => [],
                'error' => $e->getMessage()
            ], 500);
        }
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
