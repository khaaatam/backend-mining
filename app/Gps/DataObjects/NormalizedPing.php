<?php

namespace App\Gps\DataObjects;

/**
 * Normalized GPS ping — common structure regardless of provider.
 * This is what gets persisted to gps_pings table.
 */
class NormalizedPing
{
    public function __construct(
        public readonly string         $deviceId,
        public readonly float          $latitude,
        public readonly float          $longitude,
        public readonly ?float         $speed,       // km/h
        public readonly ?float         $heading,     // degrees 0–360
        public readonly ?float         $altitude,    // meters
        public readonly \Carbon\Carbon $recordedAt,
        public readonly array          $rawPayload,  // stored as JSONB for audit
    ) {}
}
