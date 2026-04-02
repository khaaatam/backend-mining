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

        // assign role admin
        $admin->assignRole('Admin');
    }
}
