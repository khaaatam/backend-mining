<?php

namespace App\Contracts\Gps;

use App\Gps\DataObjects\NormalizedPing;
use App\Gps\DataObjects\RawPing;

interface GpsProviderContract
{
    /**
     * Fetch the latest location ping for a given device ID
     * from the provider's API.
     */
    public function fetchLatest(string $deviceId): RawPing;

    /**
     * Normalize the raw provider response into a common
     * NormalizedPing structure used internally.
     */
    public function normalize(RawPing $raw): NormalizedPing;

    /**
     * Test connectivity and authentication against the provider's
     * API. Returns true on success, throws on failure.
     */
    public function testConnection(): bool;
}
