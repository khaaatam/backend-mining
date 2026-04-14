<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── gps_providers ────────────────────────────────────────────
        Schema::create('gps_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver')->default('generic'); // maps to GpsProviderManager driver key
            $table->string('base_url');
            $table->string('auth_type')->default('api_key'); // api_key|bearer|basic|oauth2
            $table->jsonb('auth_config')->default('{}');     // encrypted at app level
            $table->string('location_endpoint')->nullable(); // e.g. /api/v1/devices/{id}/location
            $table->string('history_endpoint')->nullable();
            $table->jsonb('field_map')->default('{}');       // for GenericRestProvider
            $table->unsignedSmallInteger('poll_interval')->default(30); // seconds
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── gps_pings (partitioned by month) ─────────────────────────
        // Create the parent table without primary key — partitioned tables
        // in PostgreSQL need the partition key in every index.
        DB::statement("
            CREATE TABLE gps_pings (
                id           BIGSERIAL,
                vehicle_id   BIGINT        NOT NULL,
                recorded_at  TIMESTAMPTZ   NOT NULL,
                coordinates  GEOMETRY(Point, 4326),
                latitude     DECIMAL(10,7) NOT NULL,
                longitude    DECIMAL(10,7) NOT NULL,
                speed        DECIMAL(6,2),
                heading      DECIMAL(5,2),
                altitude     DECIMAL(8,2),
                raw_payload  JSONB         NOT NULL DEFAULT '{}',
                PRIMARY KEY (id, recorded_at)
            ) PARTITION BY RANGE (recorded_at)
        ");

        // Spatial index on coordinates
        DB::statement("
            CREATE INDEX gps_pings_coordinates_idx
            ON gps_pings USING GIST (coordinates)
        ");

        // BRIN index on recorded_at — very cheap, great for time-range queries
        DB::statement("
            CREATE INDEX gps_pings_recorded_at_brin
            ON gps_pings USING BRIN (recorded_at)
        ");

        // Composite index for the most common query pattern:
        // WHERE vehicle_id = ? AND recorded_at BETWEEN ? AND ?
        DB::statement("
            CREATE INDEX gps_pings_vehicle_time_idx
            ON gps_pings (vehicle_id, recorded_at)
        ");

        // Create initial partitions: current month + next month
        $this->createMonthPartition(now());
        $this->createMonthPartition(now()->addMonth());
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS gps_pings CASCADE');
        Schema::dropIfExists('gps_providers');
    }

    private function createMonthPartition(\Carbon\Carbon $date): void
    {
        $name  = 'gps_pings_' . $date->format('Y_m');
        $start = $date->startOfMonth()->toDateString();
        $end   = $date->copy()->addMonth()->startOfMonth()->toDateString();

        DB::statement("
            CREATE TABLE {$name}
            PARTITION OF gps_pings
            FOR VALUES FROM ('{$start}') TO ('{$end}')
        ");
    }
};
