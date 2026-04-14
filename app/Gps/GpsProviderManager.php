<?php

namespace App\Gps;

use App\Contracts\Gps\GpsProviderContract;
use App\Gps\Providers\GenericRestProvider;
use App\Gps\Providers\HexagonProvider;
use App\Gps\Providers\TeltonikaProvider;
use App\Models\GpsProvider;
use InvalidArgumentException;

/**
 * Resolves the correct GpsProviderContract implementation
 * for a given GpsProvider model instance.
 *
 * Register new providers in GpsServiceProvider using:
 *   GpsProviderManager::register('teltonika', TeltonikaProvider::class);
 *
 * Or bind via the driver key stored on the GpsProvider model.
 */
class GpsProviderManager
{
    /** @var array<string, class-string<GpsProviderContract>> */
    protected array $drivers = [];

    public function __construct()
    {
        // Built-in drivers — extend in GpsServiceProvider
        $this->register('teltonika', TeltonikaProvider::class);
        $this->register('generic',   GenericRestProvider::class);
        $this->register('hexagon',   HexagonProvider::class);
    }

    /**
     * Register a driver key → provider class mapping.
     */
    public function register(string $key, string $providerClass): void
    {
        $this->drivers[$key] = $providerClass;
    }

    /**
     * Resolve a GpsProviderContract instance from a GpsProvider model.
     * The model's `driver` column determines which class is instantiated.
     */
    public function resolve(GpsProvider $model): GpsProviderContract
    {
        $driver = $model->driver ?? 'generic';

        if (! isset($this->drivers[$driver])) {
            throw new InvalidArgumentException(
                "GPS driver [{$driver}] is not registered. " .
                    "Available: " . implode(', ', array_keys($this->drivers))
            );
        }

        $class = $this->drivers[$driver];

        return new $class($model);
    }

    /**
     * Shortcut: resolve by driver key (without a model).
     * Useful for testing individual drivers.
     */
    public function make(string $key, GpsProvider $model): GpsProviderContract
    {
        if (! isset($this->drivers[$key])) {
            throw new InvalidArgumentException("GPS driver [{$key}] not registered.");
        }

        return new ($this->drivers[$key])($model);
    }
}
