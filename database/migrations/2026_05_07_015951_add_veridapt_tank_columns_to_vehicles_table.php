<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('veridapt_site_id')->nullable()->after('gps_provider_id');
            $table->string('veridapt_tank_code')->nullable()->after('veridapt_site_id');
            $table->timestamp('veridapt_tank_synced_at')->nullable()->after('veridapt_tank_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'veridapt_site_id',
                'veridapt_tank_code',
                'veridapt_tank_synced_at'
            ]);
        });
    }
};
