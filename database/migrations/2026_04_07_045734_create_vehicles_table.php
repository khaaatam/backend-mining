<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number')->unique();
            $table->string('vin')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('make');
            $table->string('model');
            $table->smallInteger('year')->nullable();
            $table->foreignId('vehicle_type_id')->constrained();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->foreignId('current_operator_id')->nullable()->constrained('users');
            $table->enum('ownership_type', ['owned', 'leased', 'rented'])->default('owned');
            $table->string('vendor')->nullable();
            $table->decimal('operating_hours', 10, 1)->default(0);
            $table->decimal('load_capacity_ton', 8, 2)->nullable();
            $table->string('payload_type')->nullable();
            $table->enum('status', ['active', 'idle', 'maintenance', 'breakdown', 'decommissioned'])->default('active');
            $table->unsignedBigInteger('gps_provider_id')->nullable();
            $table->string('gps_device_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->decimal('next_service_hours', 10, 1)->nullable();
            $table->date('stnk_expiry')->nullable();
            $table->date('kir_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE vehicles ADD COLUMN last_known_location geometry(Point, 4326)");
        DB::statement("CREATE INDEX vehicles_location_idx ON vehicles USING GIST(last_known_location)");
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
