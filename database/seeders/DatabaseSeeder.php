<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            RoleSeeder::class,
        ]);

        // akun dummy buat admin
        $admin = User::factory()->create([
            'name' => 'Admin Tambang',
            'email' => 'admin@mining.test',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Admin');

        // akun dummy buat operator
        $operator = User::factory()->create([
            'name' => 'Operator Lapangan',
            'email' => 'operator@mining.test',
            'password' => bcrypt('password'),
        ]);
        $operator->assignRole('Operator');

        $this->call([
            VehicleTypeSeeder::class,
            GpsProviderSeeder::class,
        ]);

        // Baru generate kendaraannya
        \App\Models\Vehicle::factory(20)->create();
    }
}
