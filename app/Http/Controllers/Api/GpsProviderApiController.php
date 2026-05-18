<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsProvider;
use Illuminate\Http\Request;

class GpsProviderApiController extends Controller
{
    public function index()
    {
        $providers = \App\Models\GpsProvider::withCount('vehicles')->get();
        return response()->json($providers);
    }

    public function show(GpsProvider $gpsProvider)
    {
        return response()->json($gpsProvider);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'driver' => 'required|string',
            'base_url' => 'required|url',
            'auth_type' => 'required|string',
            'auth_config' => 'required|array',
            'location_endpoint' => 'nullable|string',
            'poll_interval' => 'integer|min:10',
            'is_active' => 'boolean'
        ]);

        $provider = GpsProvider::create($validated);
        return response()->json($provider, 201);
    }

    public function update(Request $request, GpsProvider $gpsProvider)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'driver' => 'required|string',
            'base_url' => 'required|url',
            'auth_type' => 'required|string',
            'auth_config' => 'required|array',
            'location_endpoint' => 'nullable|string',
            'poll_interval' => 'integer|min:10',
            'is_active' => 'boolean'
        ]);

        $gpsProvider->update($validated);
        return response()->json($gpsProvider);
    }

    public function destroy(GpsProvider $gpsProvider)
    {
        if ($gpsProvider->vehicles()->count() > 0) {
            return response()->json([
                'message' => 'Gagal hapus! Masih ada kendaraan yang terhubung ke provider ini.'
            ], 400);
        }

        $gpsProvider->delete();
        return response()->json(['message' => 'Provider berhasil dihapus']);
    }

    public function list()
    {
        return response()->json(
            GpsProvider::where('is_active', true)->get(['id', 'name'])
        );
    }
}
