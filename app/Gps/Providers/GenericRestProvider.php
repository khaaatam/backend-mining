<?php

namespace App\Gps\Providers;

use App\Gps\DataObjects\NormalizedPing;
use App\Gps\DataObjects\RawPing;
use Carbon\Carbon;

/**
 * Config-driven provider for REST APIs that follow a standard pattern.
 * The field_map JSON on the GpsProvider model tells this class how to
 * extract fields from the response, e.g.:
 *
 * {
 *   "lat":        "data.latitude",
 *   "lng":        "data.longitude",
 *   "speed":      "data.speed_kmh",
 *   "heading":    "data.direction",
 *   "altitude":   "data.alt",
 *   "timestamp":  "data.device_time"
 * }
 *
 * Use this for providers that don't need custom parsing logic.
 */
class GenericRestProvider extends AbstractGpsProvider
{
    public function fetchLatest(string $deviceId): RawPing
    {
        try {
            $endpoint = str_replace('{id}', $deviceId, $this->model->location_endpoint);

            $response = $this->client
                ->get($endpoint)
                ->throw()
                ->json();

            return new RawPing(
                deviceId: $deviceId,
                payload: $response,
                fetchedAt: Carbon::now(),
            );
        } catch (\Throwable $e) {
            $this->handleError($e, "fetchLatest({$deviceId})");

            throw $e;
        }
    }

    public function normalize(RawPing $raw): NormalizedPing
    {
        $p = $raw->payload;

        return new NormalizedPing(
            deviceId: $raw->deviceId,
            latitude: (float) $this->resolveField($p, 'lat'),
            longitude: (float) $this->resolveField($p, 'lng'),
            speed: $this->resolveField($p, 'speed') !== null
                ? (float) $this->resolveField($p, 'speed')
                : null,
            heading: $this->resolveField($p, 'heading') !== null
                ? (float) $this->resolveField($p, 'heading')
                : null,
            altitude: $this->resolveField($p, 'altitude') !== null
                ? (float) $this->resolveField($p, 'altitude')
                : null,
            recordedAt: $this->resolveField($p, 'timestamp')
                ? Carbon::parse($this->resolveField($p, 'timestamp'))
                : $raw->fetchedAt,
            rawPayload: $p,
        );
    }

    public function testConnection(): bool
    {
        // Attempt to hit base_url — a 2xx/3xx means auth + connectivity is fine
        $response = $this->client->get('/');
        return $response->status() < 500;
    }
}
