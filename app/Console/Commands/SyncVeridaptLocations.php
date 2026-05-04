<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VeridaptService;
use App\Models\Vehicle;
use App\Models\GpsProvider;
use Illuminate\Support\Facades\DB;

class SyncVeridaptLocations extends Command
{
    protected $signature   = 'veridapt:sync-locations';
    protected $description = 'Sync GPS coordinates from Veridapt (Split Station & Truck)';

    public function handle(VeridaptService $service)
    {
        $siteId = config('services.veridapt.site_id');

        if (!$siteId) {
            $this->error('Site ID Veridapt belum di-set di .env');
            return;
        }

        // AMBIL ID PROVIDER BERDASARKAN NAMA (Karena gak ada kolom slug)
        $stationProvId = GpsProvider::where('name', 'Veridapt Fuel Station')->value('id');
        $truckProvId = GpsProvider::where('name', 'Veridapt Fuel Truck')->value('id');

        if (!$stationProvId || !$truckProvId) {
            $this->error('Salah satu Provider Veridapt belum dibuat di database!');
            return;
        }

        // 1. Sync fuel stations (T01)
        $this->info('Syncing fuel station coordinates...');
        $stations = $service->getFuelStationCoordinates($siteId);
        foreach ($stations as $station) {
            Vehicle::where('asset_number', $station['code'])
                ->where('gps_provider_id', $stationProvId) 
                ->update([
                    'last_known_location' => DB::raw("ST_GeomFromText('POINT({$station['longitude']} {$station['latitude']})', 4326)"),
                    'last_seen_at' => now(),
                ]);
        }
        $this->info(count($stations) . ' fuel stations synced.');

        // 2. Sync fuel truck positions
        $this->info('Syncing fuel truck positions...');
        $trucks = $service->getFuelTruckLastPosition($siteId);

        foreach ($trucks as $truck) {
            if (empty($truck['longitude']) || empty($truck['latitude'])) {
                $this->warn("Truk {$truck['equipment_id']} di-ignore karena tidak ada data transaksi terbaru.");
                continue;
            }
            Vehicle::where('asset_number', $truck['equipment_id'])
                ->where('gps_provider_id', $truckProvId)
                ->update([
                    'last_known_location' => DB::raw("ST_GeomFromText('POINT({$truck['longitude']} {$truck['latitude']})', 4326)"),
                    'last_seen_at' => now(),
                ]);
        }
        $this->info(count($trucks) . ' fuel truck positions updated.');
    }
}
