<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VeridaptService;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class SyncVeridaptLocations extends Command
{
    protected $signature   = 'veridapt:sync-locations';
    protected $description = 'Sync GPS coordinates from Veridapt';

    public function handle(VeridaptService $service)
    {
        $siteId = config('services.veridapt.site_id');

        if (!$siteId) {
            $this->error('Site ID Veridapt belum di-set di .env');
            return;
        }

        // 1. Sync fuel stations (T01)
        $this->info('Syncing fuel station coordinates...');
        $stations = $service->getFuelStationCoordinates($siteId);
        foreach ($stations as $station) {
            // PAKE asset_number JANGAN equipment_id
            Vehicle::where('asset_number', $station['code'])
                ->update([
                    'last_known_location' => DB::raw("ST_GeomFromText('POINT({$station['longitude']} {$station['latitude']})', 4326)"),
                    'last_seen_at' => now(),
                ]);
        }

        // 2. Sync fuel truck positions (FT-9911)
        $this->info('Syncing fuel truck positions...');
        // PAKE getFuelTruckLastPosition BIAR LANGSUNG UPDATE TANPA NUNGGU TRANSAKSI
        $trucks = $service->getFuelTruckLastPosition($siteId);

        foreach ($trucks as $truck) {
            // PAKE asset_number JANGAN equipment_id
            Vehicle::where('asset_number', $truck['equipment_id'])
                ->update([
                    'last_known_location' => DB::raw("ST_GeomFromText('POINT({$truck['longitude']} {$truck['latitude']})', 4326)"),
                    'last_seen_at' => now(),
                ]);
        }

        $this->info(count($trucks) . ' fuel truck positions updated.');
    }
}
