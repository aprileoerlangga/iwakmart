<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat role jika belum ada
        $customer = Role::firstOrCreate(['name' => 'customer'], ['guard_name' => 'web']);
        $seller = Role::firstOrCreate(['name' => 'seller'], ['guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);

        // Buat permissions jika belum ada
        $manageProducts = Permission::firstOrCreate(['name' => 'manage products'], ['guard_name' => 'web']);
        $manageOrders = Permission::firstOrCreate(['name' => 'manage orders'], ['guard_name' => 'web']);
        $manageUsers = Permission::firstOrCreate(['name' => 'manage users'], ['guard_name' => 'web']);
        $manageSellers = Permission::firstOrCreate(['name' => 'manage sellers'], ['guard_name' => 'web']);

        // Tetapkan permission ke role admin
        $admin->syncPermissions([
            $manageProducts,
            $manageOrders,
            $manageUsers,
            $manageSellers,
        ]);

        // Tetapkan permission ke role seller
        $seller->syncPermissions([
            $manageProducts,
            $manageOrders,
        ]);
    }
}
