<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin Account ─────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@marketplace.com'],
            [
                'name'               => 'Admin',
                'password'           => Hash::make('Admin@12345'),
                'role'               => 'admin',
                'email_verified_at'  => now(),
                'is_active'          => true,
            ]
        );

        // ─── Demo Vendor ───────────────────────────────────────────
        $vendor = User::firstOrCreate(
            ['email' => 'vendor@marketplace.com'],
            [
                'name'               => 'Demo Vendor',
                'password'           => Hash::make('Vendor@12345'),
                'role'               => 'vendor',
                'email_verified_at'  => now(),
                'is_active'          => true,
            ]
        );

        \App\Models\Vendor::firstOrCreate(
            ['user_id' => $vendor->id],
            [
                'store_name'  => 'Demo Store',
                'description' => 'A demo vendor store for testing.',
                'status'      => 'approved',
                'approved_at' => now(),
            ]
        );

        // ─── Demo Customer ─────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'customer@marketplace.com'],
            [
                'name'               => 'Demo Customer',
                'password'           => Hash::make('Customer@12345'),
                'role'               => 'customer',
                'email_verified_at'  => now(),
                'is_active'          => true,
            ]
        );

        $this->command->info('✅ Seeding complete:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['admin',    'admin@marketplace.com',    'Admin@12345'],
                ['vendor',   'vendor@marketplace.com',   'Vendor@12345'],
                ['customer', 'customer@marketplace.com', 'Customer@12345'],
            ]
        );
    }
}
