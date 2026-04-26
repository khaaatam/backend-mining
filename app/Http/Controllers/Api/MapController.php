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
        $vehicles = Vehicle::with('vehicleType')
            ->select([
                'vehicles.*',
                DB::raw('ST_AsGeoJSON(last_known_location) as geometry')
            ])
            ->whereNotNull('last_known_location')
            ->get();

        $features = $vehicles->map(function ($vehicle) {
            $lastSeen = $vehicle->last_seen_at ? $vehicle->last_seen_at : null;

            return [
                'type' => 'Feature',
                'geometry' => json_decode($vehicle->geometry),
                'properties' => [
                    'id' => $vehicle->id,
                    'asset_number' => $vehicle->asset_number,
                    'status' => $vehicle->status,
                    'type_key' => $vehicle->vehicleType->icon_key ?? 'unknown',
                    'type_name' => $vehicle->vehicleType->name ?? 'Unit',
                    'make' => $vehicle->make ?? 'Unknown',
                    'model' => $vehicle->model ?? '',

                    'speed' => $vehicle->speed ?? 0,
                    'heading' => $vehicle->heading ?? 0,
                    'operating_hours' => $vehicle->operating_hours ?? 0,
                    'last_seen_at' => $lastSeen ? $lastSeen->toIso8601String() : null,
                    'is_stale' => $lastSeen ? $lastSeen->diffInMinutes(now()) > 5 : true,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function singleLive(Vehicle $vehicle): JsonResponse
    {
        if (!$vehicle->last_known_location) {
            return response()->json([
                'message' => 'Vehicle has no known location.',
            ], 404);
        }

        $vehicle->load('vehicleType');

        $geometry = DB::selectOne(
            "SELECT ST_AsGeoJSON(last_known_location) as geometry FROM vehicles WHERE id = ?",
            [$vehicle->id]
        )->geometry;

        $lastSeen = $vehicle->last_seen_at ? \Carbon\Carbon::parse($vehicle->last_seen_at) : null;

        $feature = [
            'type' => 'Feature',
            'geometry' => json_decode($geometry),
            'properties' => [
                'id' => $vehicle->id,
                'asset_number' => $vehicle->asset_number,
                'status' => $vehicle->status,
                'type_key' => $vehicle->vehicleType->icon_key ?? 'unknown',
                'type_name' => $vehicle->vehicleType->name ?? 'Unit',
                'make' => $vehicle->make ?? 'Unknown',
                'model' => $vehicle->model ?? '',
                'speed' => $vehicle->speed ?? 0,
                'heading' => $vehicle->heading ?? 0,
                'operating_hours' => $vehicle->operating_hours ?? 0,
                'last_seen_at' => $lastSeen ? $lastSeen->toIso8601String() : null,
                'is_stale' => $lastSeen ? $lastSeen->diffInMinutes(now()) > 5 : true,
            ],
        ];

        return response()->json($feature);
    }
}
