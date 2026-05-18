<?php

namespace App\Gps\Providers;

use App\Gps\DataObjects\NormalizedPing;
use App\Gps\DataObjects\RawPing;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LenzguardProvider extends AbstractGpsProvider
{
    /**
     * Helper aman buat narik token dari database,
     * jaga-jaga kalau auth_config belum di-cast jadi array sama Laravel.
     */
    private function getToken(): string
    {
        $auth = $this->model->auth_config;
        if (is_array($auth)) {
            return $auth['token'] ?? '';
        }
        return json_decode($auth, true)['token'] ?? '';
    }

    public function testConnection(): bool
    {
        try {
            $url = $this->model->base_url ?? 'https://bridge.lenzguard.com/device-status';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getToken(),
            ])->get($url, [
                'devices' => '111111111'
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function fetchLatest(string $deviceId): RawPing
    {
        try {
            $url = $this->model->base_url ?? 'https://bridge.lenzguard.com/device-status';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getToken(),
            ])->get($url, [
                'devices' => $deviceId
            ])->throw()->json();

            $item = $response['results'][0] ?? [];

            return new RawPing(
                deviceId: $deviceId,
                payload: $item,
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
        $status = $p['data']['status'][0] ?? [];

        return new NormalizedPing(
            deviceId: $raw->deviceId,
            latitude: (float) ($status['mlat'] ?? 0),
            longitude: (float) ($status['mlng'] ?? 0),
            speed: (float) ($status['sp'] ?? 0),
            heading: (float) ($status['hx'] ?? 0),
            altitude: null,
            recordedAt: isset($status['gt']) ? Carbon::parse($status['gt']) : $raw->fetchedAt,
            rawPayload: $p,
        );
    }
}
