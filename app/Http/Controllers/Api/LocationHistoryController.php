<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class LocationHistoryController extends Controller
{
    /**
     * Get historical GPS path and statistics for a vehicle.
     */
    public function index(Request $request)
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

        // Maksimal penarikan 3 hari untuk menjaga performa server & memori browser
        if ($from->diffInDays($to) > 3) {
            return response()->json([
                'success' => false,
                'message' => 'Rentang waktu maksimal yang diizinkan adalah 3 hari.'
            ], 422);
        }

        // =====================================================================
        // STEP 1: AMBIL STATISTIK & HITUNG JARAK VIA POSTGIS (ST_Length)
        // =====================================================================
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

        // Kalau datanya kosong (truk nggak jalan di jam segitu), kembalikan Empty GeoJSON
        if (!$stats || $stats->point_count == 0) {
            return response()->json([
                'type' => 'FeatureCollection',
                'features' => [],
                'meta' => [
                    'distance_km' => 0,
                    'duration_min' => 0,
                    'avg_speed' => 0,
                    'max_speed' => 0,
                    'point_count' => 0
                ]
            ]);
        }

        // Hitung Total Jarak (Distance) pakai fungsi Spasial PostGIS
        $distanceQuery = DB::table('gps_pings')
            ->where('vehicle_id', $vehicleId)
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw('ST_Length(ST_MakeLine(ST_SetSRID(ST_MakePoint(longitude, latitude), 4326) ORDER BY recorded_at)::geography) / 1000 as distance_km')
            ->first();

        $distanceKm = $distanceQuery ? (float) $distanceQuery->distance_km : 0;
        $durationMin = Carbon::parse($stats->start_time)->diffInMinutes(Carbon::parse($stats->end_time));

        // =====================================================================
        // STEP 2: TARIK KOORDINAT & DOWNSAMPLING
        // =====================================================================
        $query = DB::table('gps_pings')
            ->where('vehicle_id', $vehicleId)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at', 'asc')
            ->select('latitude', 'longitude', 'speed', 'recorded_at');

        $pings = $query->get();

        // Downsampling (Mirip RDP): Kalau titik > 2000, kita lompat-lompat nariknya
        // Biar ukuran JSON yang dikirim nggak bikin browser meledak.
        $pointCount = $pings->count();
        if ($pointCount > 2000) {
            $step = ceil($pointCount / 1000);
            $pings = $pings->nth($step); // Ambil setiap baris ke-N
        }

        // =====================================================================
        // STEP 3: GEOJSON SPEED SEGMENTING
        // Memecah 1 rute panjang jadi segmen-segmen warna berdasarkan kecepatan
        // =====================================================================
        $features = [];
        $currentSegment = [];
        $currentTier = null;

        foreach ($pings as $ping) {
            $speed = (float) $ping->speed;

            // Kategori warna sesuai dokumen Asana
            if ($speed < 20) {
                $tier = 'tier1';
            } elseif ($speed < 35) {
                $tier = 'tier2';
            } elseif ($speed < 50) {
                $tier = 'tier3';
            } else {
                $tier = 'tier4';
            }

            $coord = [(float)$ping->longitude, (float)$ping->latitude];

            if ($currentTier !== $tier) {
                // Kalau kecepatannya berubah, kita tutup garis lama, dan bikin garis baru
                if (!empty($currentSegment)) {
                    // Masukin kordinat baru ini ke ujung garis lama biar garisnya nggak putus/bolong di map
                    $currentSegment[] = $coord;
                    $features[] = $this->createGeoJsonFeature($currentSegment, $currentTier);
                    $currentSegment = [$coord]; // Mulai garis baru
                } else {
                    $currentSegment = [$coord];
                }
                $currentTier = $tier;
            } else {
                // Kalau kecepatan masih sama, lanjutin gambar garisnya
                $currentSegment[] = $coord;
            }
        }

        // Masukin segmen terakhir yang tersisa ke array features
        if (count($currentSegment) > 1) {
            $features[] = $this->createGeoJsonFeature($currentSegment, $currentTier);
        }

        // 4. Return Output sesuai Standar Dokumen (FeatureCollection)
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => [
                'distance_km' => round($distanceKm, 2),
                'duration_min' => $durationMin,
                'avg_speed' => round((float)$stats->avg_speed, 2),
                'max_speed' => round((float)$stats->max_speed, 2),
                'point_count' => $pointCount // Kita tampilin count asli sblm di-downsample
            ]
        ]);
    }

    /**
     * Helper function untuk ngebentuk array GeoJSON Feature
     */
    private function createGeoJsonFeature($coordinates, $speedTier)
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
