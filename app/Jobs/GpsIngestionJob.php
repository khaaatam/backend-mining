<?php

namespace App\Jobs;

use App\Gps\GpsProviderManager;
use App\Models\GpsPing;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GpsIngestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $vehicleId,
    ) {}

    public function handle(GpsProviderManager $manager): void
    {
        $vehicle = Vehicle::with('gpsProvider')
            ->findOrFail($this->vehicleId);

        if (! $vehicle->gps_provider_id || ! $vehicle->gps_device_id) {
            Log::warning("[GpsIngestion] Vehicle {$this->vehicleId} has no GPS device linked — skipping.");
            return;
        }

        $provider = $manager->resolve($vehicle->gpsProvider);

        $raw        = $provider->fetchLatest($vehicle->gps_device_id);
        $normalized = $provider->normalize($raw);

        DB::transaction(function () use ($normalized, $vehicle) {
            GpsPing::create([
                'vehicle_id'  => $vehicle->id,
                'recorded_at' => $normalized->recordedAt,
                'speed'       => $normalized->speed,
                'heading'     => $normalized->heading,
                'altitude'    => $normalized->altitude,
                'raw_payload' => $normalized->rawPayload,
                // PostGIS point — set via raw expression
                // 'coordinates' handled in GpsPing::creating() observer
                // using: DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)")
                'latitude'    => $normalized->latitude,
                'longitude'   => $normalized->longitude,
            ]);

            // Update vehicle's last known location + last_seen_at
            $vehicle->update([
                'last_seen_at' => $normalized->recordedAt,
            ]);

            DB::statement("
                UPDATE vehicles
                SET last_known_location = ST_SetSRID(ST_MakePoint(?, ?), 4326)
                WHERE id = ?
            ", [$normalized->longitude, $normalized->latitude, $vehicle->id]);
        });
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[GpsIngestion] Job failed for vehicle {$this->vehicleId}", [
            'error' => $e->getMessage(),
        ]);
    }
}
