<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'warehouse_id' => null,
        ]);

        // Create warehouse managers for each warehouse
        for ($i = 1; $i <= 4; $i++) {
            User::create([
                'name' => "Warehouse Manager {$i}",
                'email' => "manager{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'warehouse_manager',
                'warehouse_id' => $i,
            ]);
        }

        // Create staff members for each warehouse
        for ($i = 1; $i <= 4; $i++) {
            User::create([
                'name' => "Staff Member {$i}",
                'email' => "staff{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'staff',
                'warehouse_id' => $i,
            ]);
        }
    }
}
