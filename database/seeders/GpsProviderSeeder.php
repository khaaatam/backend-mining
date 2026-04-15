<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GpsProvider;

class GpsProviderSeeder extends Seeder
{
    public function run(): void
    {
        GpsProvider::create([
            'name' => 'Hexagon Mining',
            'driver' => 'hexagon',
            'base_url' => 'http://127.0.0.1:3000/api/v1/rest',
            'auth_config' => [
                'auth_type' => 'basic',
                'username' => 'admin',
                'password' => 'secret'
            ],
            'is_active' => true
        ]);
    }
}
