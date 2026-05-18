<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Vehicle;
use App\Models\GpsProvider;

class SyncHexagonLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracking:sync-hexagon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize real-time vehicle locations from the Hexagon API.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Hexagon GPS synchronization process...');

        $providerDb = GpsProvider::where('name', 'Hexagon Mining')->first();
        if (!$providerDb) {
            $this->error('Hexagon GPS provider not found in the database. Aborting sync.');
            Log::error('SyncHexagonLocations: Provider not found.');
            return;
        }

        $url = 'http://192.168.111.103:3000/api/v1/rest/traveling';
        $this->info("Fetching payload from: {$url}");

        try {
            $username = env('HEXAGON_API_USERNAME');
            $password = env('HEXAGON_API_PASSWORD');

            $response = Http::timeout(10)->withBasicAuth($username, $password)->get($url);;

            if ($response->failed()) {
                $this->error("Failed to fetch data from Hexagon API. HTTP Status: {$response->status()}");
                Log::error("SyncHexagonLocations: API request failed with status {$response->status()}");
                return;
            }

            $travelingData = $response->json();

            if (!is_array($travelingData)) {
                $this->error('Invalid API response format. Expected an array.');
                Log::error('SyncHexagonLocations: Invalid API response format.');
                return;
            }

            $recordCount = count($travelingData);
            $this->info("Successfully fetched {$recordCount} records. Processing payload...");

            $syncedCount = 0;

            foreach ($travelingData as $data) {
                if (!isset($data['equipment_id'], $data['latitude'], $data['longitude'])) {
                    continue;
                }

                $equipmentId = $data['equipment_id'];
                $lat = $data['latitude'] / 3600000;
                $lng = $data['longitude'] / 3600000;
                $speed = $data['velocity'] ?? 0;
                $heading = $data['heading'] ?? 0;

                $timestamp = isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : now();

                // TODO: Remove this override once the staging database partition issue for historical dates is resolved.
                $timestamp = now();

                $vehicle = Vehicle::where('equipment_id', (string)$equipmentId)
                    ->where('gps_provider_id', $providerDb->id)
                    ->first();

                if (!$vehicle) {
                    continue;
                }

                DB::table('vehicles')->where('id', $vehicle->id)->update([
                    'last_known_location' => DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)"),
                    'speed' => $speed,
                    'heading' => $heading,
                    'last_seen_at' => $timestamp,
                    'status' => $speed > 0 ? 'active' : 'idle',
                    'updated_at' => now(),
                ]);

                $syncedCount++;
            }

            $this->info("Synchronization complete. Successfully updated {$syncedCount} out of {$recordCount} vehicles.");
        } catch (\Exception $e) {
            $this->error('A critical error occurred during Hexagon sync: ' . $e->getMessage());
            Log::error('SyncHexagonLocations Exception: ' . $e->getMessage());
        }
    }
}
