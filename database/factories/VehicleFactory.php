<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_number' => 'VEH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'vin' => $this->faker->unique()->bothify('*****************'),
            'plate_number' => strtoupper($this->faker->bothify('B #### ???')),
            'make' => $this->faker->randomElement(['Komatsu', 'Caterpillar', 'Hino', 'Toyota']),
            'model' => $this->faker->word(),
            'year' => $this->faker->year(),
            'vehicle_type_id' => \App\Models\VehicleType::pluck('id')->random(), // Ngambil dari tipe yang udah di-seed
            'ownership_type' => $this->faker->randomElement(['owned', 'leased', 'rented']),
            'status' => $this->faker->randomElement(['active', 'idle', 'maintenance', 'breakdown', 'decommissioned']),
            'operating_hours' => $this->faker->randomFloat(1, 1000, 20000),

            // Data Compliance buat ngetes Expiry Alert Logic
            'stnk_expiry' => $this->faker->dateTimeBetween('-1 month', '+2 years'),
            'kir_expiry' => $this->faker->dateTimeBetween('-1 month', '+1 year'),
            'insurance_expiry' => $this->faker->dateTimeBetween('-1 month', '+1 year'),
            'last_service_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'next_service_date' => $this->faker->dateTimeBetween('now', '+6 months'),
        ];
    }
}
