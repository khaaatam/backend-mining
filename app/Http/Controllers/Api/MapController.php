<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    public function live(): JsonResponse
    {
        // Query kendaraan yang punya lokasi terakhir
        // Kita join dengan vehicle_types untuk dapet icon_key buat marker di peta
        $vehicles = Vehicle::query()
            ->select([
                'vehicles.id',
                'vehicles.asset_number',
                'vehicles.status',
                'vehicles.last_seen_at',
                'vehicles.operating_hours', // Tambahan info buat popup
                'vehicle_types.icon_key as type_key',
                // Ambil koordinat langsung sebagai GeoJSON geometry string
                DB::raw('ST_AsGeoJSON(last_known_location) as geometry')
            ])
            ->join('vehicle_types', 'vehicles.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereNotNull('last_known_location')
            ->get();

        // Mapping ke format FeatureCollection GeoJSON
        $features = $vehicles->map(function ($vehicle) {
            $lastSeen = $vehicle->last_seen_at ? $vehicle->last_seen_at : null;

            return [
                'type' => 'Feature',
                'geometry' => json_decode($vehicle->geometry),
                'properties' => [
                    'id' => $vehicle->id,
                    'asset_number' => $vehicle->asset_number,
                    'status' => $vehicle->status,
                    'type_key' => $vehicle->type_key,
                    'last_seen_at' => $lastSeen ? $lastSeen->toIso8601String() : null,
                    // Tambahkan is_stale jika data lebih dari 5 menit
                    'is_stale' => $lastSeen ? $lastSeen->diffInMinutes(now()) > 5 : true,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
