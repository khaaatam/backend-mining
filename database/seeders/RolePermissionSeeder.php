<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Mengelola hak akses modul pengguna
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
            // Mengelola hak akses modul kendaraan
            'view vehicles',
            'create vehicles',
            'edit vehicles',
            'delete vehicles',
            'change vehicle status',
            'assign gps device',
            // Mengelola hak akses modul penyedia GPS
            'view gps providers',
            'manage gps providers',
            'view ingestion health',
            // Mengelola hak akses peta dan pelacakan
            'view live map',
            'view location history',
            'replay location history',
            // Mengelola hak akses overlay peta
            'upload overlays',
            'delete overlays',
            'toggle overlays',
            // Mengelola hak akses dasbor dan laporan
            'view dashboard',
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Role admin mendapatkan semua hak akses tanpa terkecuali
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // Role operator mendapatkan hak akses operasional harian namun tidak dapat menghapus data kendaraan
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'view vehicles',
            'create vehicles',
            'edit vehicles',
            'change vehicle status',
            'assign gps device',
            'view gps providers',
            'view ingestion health',
            'view live map',
            'view location history',
            'replay location history',
            'upload overlays',
            'toggle overlays',
            'view dashboard',
            'view reports',
            'export reports',
        ]);

        // Role viewer hanya mendapatkan hak akses untuk membaca dan melihat data saja
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'view vehicles',
            'view live map',
            'view location history',
            'replay location history',
            'toggle overlays',
            'view dashboard',
            'view reports',
        ]);
    }
}
