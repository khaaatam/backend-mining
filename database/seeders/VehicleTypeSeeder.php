<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Haul Truck', 'category' => 'heavy_equipment', 'icon_key' => 'truck-dump'],
            ['name' => 'Excavator', 'category' => 'heavy_equipment', 'icon_key' => 'excavator'],
            ['name' => 'Bulldozer', 'category' => 'heavy_equipment', 'icon_key' => 'bulldozer'],
            ['name' => 'Grader', 'category' => 'heavy_equipment', 'icon_key' => 'grader'],
            ['name' => 'Compactor', 'category' => 'heavy_equipment', 'icon_key' => 'compactor'],
            ['name' => 'Water Truck', 'category' => 'support', 'icon_key' => 'truck-water'],
            ['name' => 'Fuel Truck', 'category' => 'support', 'icon_key' => 'truck-fuel'],
            ['name' => 'Light Vehicle', 'category' => 'personnel', 'icon_key' => 'car'],
            ['name' => 'Ambulance', 'category' => 'personnel', 'icon_key' => 'ambulance'],
        ];

        foreach ($types as $type) {
            VehicleType::create($type);
        }
    }
}
