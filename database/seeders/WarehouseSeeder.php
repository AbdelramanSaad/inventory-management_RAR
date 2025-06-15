<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Main Warehouse',
                'location' => 'New York, NY',
            ],
            [
                'name' => 'East Coast Distribution Center',
                'location' => 'Boston, MA',
            ],
            [
                'name' => 'West Coast Distribution Center',
                'location' => 'Los Angeles, CA',
            ],
            [
                'name' => 'Central Warehouse',
                'location' => 'Chicago, IL',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
