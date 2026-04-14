<?php

namespace App\Gps\Providers;

use App\Gps\DataObjects\NormalizedPing;
use App\Gps\DataObjects\RawPing;
use Carbon\Carbon;

class TeltonikaProvider extends AbstractGpsProvider
{
    public function fetchLatest(string $deviceId): RawPing
    {
        try {
            $response = $this->client
                ->get("/api/devices/{$deviceId}/location")
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
        // Teltonika response structure:
        // { "deviceId": "...", "lat": 0.0, "lng": 0.0, "speed": 0, "angle": 0, "altitude": 0, "timestamp": "..." }
        $p = $raw->payload;

        return new NormalizedPing(
            deviceId: $raw->deviceId,
            latitude: (float) ($p['lat'] ?? 0),
            longitude: (float) ($p['lng'] ?? 0),
            speed: isset($p['speed'])    ? (float) $p['speed']    : null,
            heading: isset($p['angle'])    ? (float) $p['angle']    : null,
            altitude: isset($p['altitude']) ? (float) $p['altitude'] : null,
            recordedAt: Carbon::parse($p['timestamp'] ?? $raw->fetchedAt),
            rawPayload: $p,
        );
    }

    public function testConnection(): bool
    {
        $response = $this->client->get('/api/ping');
        return $response->successful();
    }
}
