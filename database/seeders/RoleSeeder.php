<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // bikin 3 role sesuai spesifikasi dokumen
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Operator']);
        Role::create(['name' => 'Viewer']);
    }
}
