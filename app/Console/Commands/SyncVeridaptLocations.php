<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VeridaptService;
use App\Models\Vehicle;
// use Illuminate\Support\Facades\DB;

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

        // 1. Sync fuel stations — run daily or on demand
        $this->info('Syncing fuel station coordinates...');
        $stations = $service->getFuelStationCoordinates($siteId);
        foreach ($stations as $station) {
            Vehicle::where('veridapt_asset_id', $station['asset_id'])
                ->update([
                    'latitude'   => $station['latitude'],
                    'longitude'  => $station['longitude'],
                    'synced_at'  => now(),
                ]);
        }
        $this->info(count($stations) . ' fuel stations synced.');

        // 2. Sync fuel truck last positions — run more frequently
        $this->info('Syncing fuel truck positions via transactions...');
        $updatedFrom = now()->subMinutes(30)->toIso8601String();
        $transactions = $service->getLatestFuelTruckTransactions($siteId, $updatedFrom);

        foreach ($transactions as $tx) {
            Vehicle::where('equipment_id', $tx['target_equipment'])
                ->update([
                    'latitude'        => $tx['latitude'],
                    'longitude'       => $tx['longitude'],
                    'last_seen_at'    => $tx['collected_at'],
                    'synced_at'       => now(),
                ]);
        }
        $this->info(count($transactions) . ' fuel truck positions updated.');
    }
}
