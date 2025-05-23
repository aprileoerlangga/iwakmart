<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan RoleSeeder terlebih dahulu
        $this->call([
            RoleSeeder::class,
        ]);

        // User::factory(10)->create();

        // Buat user test
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // Berikan role 'customer' ke user test
        $user->assignRole('customer');
        
        // Buat user admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        
        // Berikan role 'admin' ke user admin
        $admin->assignRole('admin');
        
        // Buat user penjual
        $seller = User::create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'password' => Hash::make('password'),
        ]);
        
        // Berikan role 'seller' ke user penjual
        $seller->assignRole('seller');
    }
}