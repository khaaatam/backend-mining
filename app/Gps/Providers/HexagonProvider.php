<?php

namespace App\Gps\Providers;

use App\Gps\DataObjects\NormalizedPing;
use App\Gps\DataObjects\RawPing;
use Carbon\Carbon;

class HexagonProvider extends AbstractGpsProvider
{
    /**
     * Ambil data terbaru dari API Hexagon.
     */
    public function fetchLatest(string $deviceId): RawPing
    {
        try {
            // Hexagon biasanya butuh ID di query params
            $response = $this->client
                ->get($this->model->location_endpoint ?? "/traveling", [
                    'id' => $deviceId
                ])
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

    /**
     * Normalisasi data mentah Hexagon ke format sistem.
     * Logic: koordinat mentah dibagi 3.600.000
     */
    public function normalize(RawPing $raw): NormalizedPing
    {
        $p = $raw->payload;

        return new NormalizedPing(
            deviceId: $raw->deviceId,
            // bagi 3.600.000 sesuai dokumentasi
            latitude: (float) (($p['latitude'] ?? 0) / 3600000),
            longitude: (float) (($p['longitude'] ?? 0) / 3600000),
            speed: isset($p['velocity'])  ? (float) $p['velocity'] : null,
            heading: isset($p['heading'])   ? (float) $p['heading']  : null,
            altitude: isset($p['altitude'])  ? (float) $p['altitude'] : null,
            recordedAt: Carbon::parse($p['timestamp'] ?? $raw->fetchedAt),
            rawPayload: $p,
        );
    }

    public function testConnection(): bool
    {
        // Test nembak base URL dengan auth yang udah ada di Abstract
        $response = $this->client->get('/');
        return $response->successful();
    }
}
