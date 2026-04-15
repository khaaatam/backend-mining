<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsProvider;
use Illuminate\Http\Request;

class GpsProviderApiController extends Controller
{
    // Ambil semua daftar provider buat ditampilin di tabel Vue
    public function index()
    {
        return response()->json(GpsProvider::all());

        $providers = \App\Models\GpsProvider::withCount('vehicles')->get();

        return response()->json($providers);
    }

    // Ambil detail data buat diisi ke form edit di Vue
    public function show(GpsProvider $gpsProvider)
    {
        return response()->json($gpsProvider);
    }

    // Simpan provider baru (Inputan dari Modal Form di Vue nanti)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'driver' => 'required|string', // Contoh: 'hexagon'
            'base_url' => 'required|url',
            'auth_type' => 'required|string', // api_key, bearer, basic
            'auth_config' => 'required|array', // JSON config
            'location_endpoint' => 'nullable|string', // Isi: '/Traveling'
            'poll_interval' => 'integer|min:10',
            'is_active' => 'boolean'
        ]);

        $provider = GpsProvider::create($validated);
        return response()->json($provider, 201);
    }

    // Simpan perubahan data
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

    // Hapus provider
    public function destroy(GpsProvider $gpsProvider)
    {
        // Opsional: Cek dulu kalau masih ada kendaraan yang nempel
        if ($gpsProvider->vehicles()->count() > 0) {
            return response()->json([
                'message' => 'Gagal hapus! Masih ada kendaraan yang terhubung ke provider ini.'
            ], 400);
        }

        $gpsProvider->delete();
        return response()->json(['message' => 'Provider berhasil dihapus']);
    }

    // Buat dropdown di halaman assignment kendaraan
    public function list()
    {
        return response()->json(
            GpsProvider::where('is_active', true)->get(['id', 'name'])
        );
    }
}
