<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Vehicle;
use App\Jobs\GpsIngestionJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('gps:create-partition')->monthlyOn(1, '00:05');

Schedule::command('veridapt:sync-locations')->hourly();

Schedule::call(function () {
    $vehicles = Vehicle::whereNotNull('gps_provider_id')
        ->whereNotNull('gps_device_id')
        ->get();

    foreach ($vehicles as $vehicle) {
        GpsIngestionJob::dispatch($vehicle->id);
    }
})->everyMinute();
