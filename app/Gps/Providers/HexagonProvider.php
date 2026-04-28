<?php

namespace App\Gps\Providers;

use App\Gps\DataObjects\NormalizedPing;
use App\Gps\DataObjects\RawPing;
use Carbon\Carbon;

class HexagonProvider extends AbstractGpsProvider
{
    /**
     * Ambil data terbaru dari API Hexagon.
     * Menggunakan endpoint /Traveling sesuai dokumen task.
     */
    public function fetchLatest(string $deviceId): RawPing
    {
        try {
            // Sesuai catatan: Hexagon butuh ID di query params
            // Kita gunakan $this->client yang sudah membawa Base URL dan Auth dari Abstract
            $response = $this->client
                ->get("/Traveling", [
                    'id' => $deviceId
                ])
                ->throw()
                ->json();

            // Sesuai requirement: data biasanya ada di array atau langsung object
            // Kita ambil elemen pertama jika response-nya berupa list
            $data = isset($response[0]) ? $response[0] : $response;

            return new RawPing(
                deviceId: $deviceId,
                payload: $data,
                fetchedAt: Carbon::now(),
            );
        } catch (\Throwable $e) {
            $this->handleError($e, "fetchLatest({$deviceId})");
            throw $e;
        }
    }

    /**
     * Normalisasi data mentah Hexagon ke format sistem.
     * 1. Koordinat wgs84 milliseconds / 3600000.
     * 2. Speed dari field 'velocity' (KPH).
     * 3. Heading dari field 'heading'.
     */
    public function normalize(RawPing $raw): NormalizedPing
    {
        $p = $raw->payload;

        return new NormalizedPing(
            deviceId: $raw->deviceId,
            // Konversi Milliseconds ke Decimal Degrees
            latitude: (float) (($p['latitude'] ?? 0) / 3600000),
            longitude: (float) (($p['longitude'] ?? 0) / 3600000),
            // Mapping field sesuai requirement Hexagon
            speed: (float) ($p['velocity'] ?? 0),
            heading: (float) ($p['heading'] ?? 0),
            altitude: null, // Hexagon tidak menyediakan altitude [cite: 2775]
            recordedAt: $raw->fetchedAt,
            rawPayload: $p,
        );
    }

    /**
     * Test koneksi menggunakan Basic Auth yang sudah di-setup di Abstract.
     */
    public function testConnection(): bool
    {
        try {
            // Tembak endpoint Traveling sesuai dokumen task [cite: 1391]
            $response = $this->client->get('/Traveling', ['limit' => 1]);
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
