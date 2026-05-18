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
    protected $description = 'Sync GPS coordinates from Veridapt (Split Station & Truck with Tank resolution)';

    public function handle(VeridaptService $service)
    {
        $siteId = config('services.veridapt.site_id');

        if (!$siteId) {
            $this->error('Site ID Veridapt belum di-set di .env');
            return;
        }

        // AMBIL ID PROVIDER BERDASARKAN NAMA
        $stationProvId = GpsProvider::where('name', 'Veridapt Fuel Station')->value('id');
        $truckProvId = GpsProvider::where('name', 'Veridapt Fuel Truck')->value('id');

        if (!$stationProvId || !$truckProvId) {
            $this->error('Salah satu Provider Veridapt belum dibuat di database!');
            return;
        }

        $this->info('Syncing fuel station coordinates...');
        $stations = $service->getFuelStationCoordinates($siteId);

        foreach ($stations as $station) {
            Vehicle::where('asset_number', $station['code'])
                ->where('gps_provider_id', $stationProvId)
                ->update([
                    'veridapt_site_id' => $siteId, // Isi kolom baru
                    'last_known_location' => DB::raw("ST_GeomFromText('POINT({$station['longitude']} {$station['latitude']})', 4326)"),
                    'last_seen_at' => now(),
                ]);
        }
        $this->info(count($stations) . ' fuel stations synced.');

        $this->info('Syncing fuel truck positions...');

        $recentTransactions = $service->getLatestFuelTruckTransactions($siteId, now()->subDay()->toIso8601String());

        $truckTankMapping = [];
        foreach ($recentTransactions as $tx) {
            if (!isset($truckTankMapping[$tx['target_equipment']])) {
                $truckTankMapping[$tx['target_equipment']] = $tx['source'];
            }
        }

        $trucks = $service->getFuelTruckLastPosition($siteId);

        foreach ($trucks as $truck) {
            $equipmentId = $truck['equipment_id'];

            if (empty($truck['longitude']) || empty($truck['latitude'])) {
                $this->warn("Truk {$equipmentId} di-ignore karena tidak ada data posisi valid.");
                continue;
            }

            $tankCode = $truckTankMapping[$equipmentId] ?? null;

            Vehicle::where('asset_number', $equipmentId)
                ->where('gps_provider_id', $truckProvId)
                ->update([
                    'veridapt_site_id' => $siteId,
                    'veridapt_tank_code' => $tankCode,
                    'veridapt_tank_synced_at' => now(),
                    'last_known_location' => DB::raw("ST_GeomFromText('POINT({$truck['longitude']} {$truck['latitude']})', 4326)"),
                    'last_seen_at' => now(),
                ]);
        }

        $this->info(count($trucks) . ' fuel truck positions updated.');
    }
}
