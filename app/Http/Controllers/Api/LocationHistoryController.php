<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class LocationHistoryController extends Controller
{
    /**
     * Get historical GPS path and statistics for a vehicle.
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Validasi Input
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $vehicleId = $request->vehicle_id;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to);

        // 2. Tarik Data Vehicle & Tipe Secara Manual (Anti-Relationship Error)
        $vehicle = Vehicle::find($vehicleId);
        $vType = DB::table('vehicle_types')->find($vehicle->vehicle_type_id);

        // 3. Ambil Statistik Perjalanan (recorded_at)
        $stats = DB::table('gps_pings')
            ->where('vehicle_id', $vehicleId)
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw('
                COUNT(id) as point_count,
                MAX(speed) as max_speed,
                AVG(speed) as avg_speed,
                MIN(recorded_at) as start_time,
                MAX(recorded_at) as end_time
            ')
            ->first();

        // Kalau Zonk, kirim meta kosong tapi info kendaraan tetep ada
        if (!$stats || $stats->point_count == 2) {
            return response()->json([
                'type' => 'FeatureCollection',
                'features' => [],
                'meta' => $this->buildMeta($stats, 0, 0, $vehicle, $vType)
            ]);
        }

        // 4. Hitung Jarak pake PostGIS (kolom: coordinates)
        $distanceQuery = DB::table('gps_pings')
            ->where('vehicle_id', $vehicleId)
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw('ST_Length(ST_MakeLine(coordinates::geometry ORDER BY recorded_at)::geography) / 1000 as distance_km')
            ->first();

        $distanceKm = $distanceQuery ? (float) $distanceQuery->distance_km : 0; 
        $durationMin = Carbon::parse($stats->start_time)->diffInMinutes(Carbon::parse($stats->end_time));

        // 5. Tarik Pings buat Gambar Rute
        $pings = DB::table('gps_pings')
            ->where('vehicle_id', $vehicleId)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at', 'asc')
            ->select('latitude', 'longitude', 'speed')
            ->get();

        // 6. Segmentasi Warna Kecepatan (4 Tier)
        $features = [];
        $currentSegment = [];
        $currentTier = null;

        foreach ($pings as $ping) {
            $speed = (float) $ping->speed;

            if ($speed < 20) $tier = 'tier1';
            elseif ($speed < 35) $tier = 'tier2';
            elseif ($speed < 50) $tier = 'tier3';
            else $tier = 'tier4';

            $coord = [(float)$ping->longitude, (float)$ping->latitude];

            if ($currentTier !== $tier) {
                if (!empty($currentSegment)) {
                    $currentSegment[] = $coord;
                    $features[] = $this->createGeoJsonFeature($currentSegment, $currentTier);
                    $currentSegment = [$coord];
                } else {
                    $currentSegment = [$coord];
                }
                $currentTier = $tier;
            } else {
                $currentSegment[] = $coord;
            }
        }

        if (count($currentSegment) > 1) {
            $features[] = $this->createGeoJsonFeature($currentSegment, $currentTier);
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => $this->buildMeta($stats, $distanceKm, $durationMin, $vehicle, $vType)
        ]);
    }

    /**
     * Build meta information for sidebar and header.
     */
    private function buildMeta($stats, float $distance, int $duration, $vehicle, $vType): array
    {
        return [
            'distance_km' => round($distance, 2),
            'duration_min' => $duration,
            'avg_speed' => round((float)($stats->avg_speed ?? 0), 2),
            'max_speed' => round((float)($stats->max_speed ?? 0), 2),
            'point_count' => $stats->point_count ?? 0,
            'asset_info' => [
                'number' => $vehicle->asset_number ?? 'ID: ' . $vehicle->id,
                'make'   => $vehicle->make ?? 'Unknown',
                'model'  => $vehicle->model ?? 'Vehicle',
                'type'   => $vType->name ?? 'Haul Truck'
            ]
        ];
    }

    /**
     * Create individual GeoJSON Feature.
     */
    private function createGeoJsonFeature(array $coordinates, ?string $speedTier): array
    {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $coordinates
            ],
            'properties' => [
                'speed_tier' => $speedTier
            ]
        ];
    }
}
