<?php

namespace App\Gps\DataObjects;

/**
 * Holds the raw, unprocessed response from a GPS provider API.
 * The payload array contains whatever the provider returns —
 * structure differs per provider.
 */
class RawPing
{
    public function __construct(
        public readonly string $deviceId,
        public readonly array  $payload,
        public readonly \Carbon\Carbon $fetchedAt,
    ) {}
}
