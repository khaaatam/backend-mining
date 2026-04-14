<?php

namespace App\Providers;

use App\Gps\GpsProviderManager;
use Illuminate\Support\ServiceProvider;

class GpsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register GpsProviderManager as a singleton
        $this->app->singleton(GpsProviderManager::class, function () {
            $manager = new GpsProviderManager();

            // Register additional drivers here as the client adds GPS providers.
            // e.g: $manager->register('concox', \App\Gps\Providers\ConcoxProvider::class);

            return $manager;
        });
    }

    public function boot(): void
    {
        // Schedule GPS ingestion for all vehicles with an active GPS device.
        // Each vehicle gets its own job dispatched to the 'gps' queue.
        // The scheduler runs every minute; the poll_interval on each provider
        // is respected inside the job (skip if last ping is recent enough).
        \Illuminate\Support\Facades\Schedule::call(function () {
            \App\Models\Vehicle::query()
                ->whereNotNull('gps_device_id')
                ->whereNotNull('gps_provider_id')
                ->whereHas('gpsProvider', fn($q) => $q->where('is_active', true))
                ->pluck('id')
                ->each(function (int $vehicleId) {
                    // Dispatch job ke antrean khusus 'gps'
                    \App\Jobs\GpsIngestionJob::dispatch($vehicleId)->onQueue('gps');
                });
        })->everyMinute()->name('gps-ingestion')->withoutOverlapping();
    }
}
