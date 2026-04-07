<?php

namespace Database\Factories;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $status = $this->faker->randomElement(['active', 'idle', 'maintenance', 'breakdown']);

        return [
            'asset_number' => 'VEH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'vin' => $this->faker->uuid(),
            'plate_number' => strtoupper($this->faker->bothify('B #### ???')),
            'make' => $this->faker->randomElement(['Caterpillar', 'Komatsu', 'Volvo', 'Scania', 'Hino']),
            'model' => $this->faker->bothify('HD-###'),
            'year' => $this->faker->numberBetween(2015, 2024),
            'vehicle_type_id' => VehicleType::inRandomOrder()->first()->id ?? VehicleType::factory(),
            'ownership_type' => $this->faker->randomElement(['owned', 'leased']),
            'operating_hours' => $this->faker->randomFloat(1, 100, 15000),
            'status' => $status,
            'stnk_expiry' => $this->faker->dateTimeBetween('-1 months', '+1 years')->format('Y-m-d'),
            'kir_expiry' => $this->faker->dateTimeBetween('-1 months', '+6 months')->format('Y-m-d'),
        ];
    }
}
