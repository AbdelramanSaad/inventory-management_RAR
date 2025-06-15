<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['electronics', 'furniture', 'clothing', 'other'];
        
        // Create 5 inventory items for each warehouse
        for ($warehouseId = 1; $warehouseId <= 4; $warehouseId++) {
            // Get the warehouse manager for this warehouse
            $warehouseManager = User::where('warehouse_id', $warehouseId)
                ->where('role', 'warehouse_manager')
                ->first();
                
            for ($i = 1; $i <= 5; $i++) {
                $category = $categories[array_rand($categories)];
                $quantity = rand(5, 100);
                $minStockLevel = rand(5, 20);
                
                InventoryItem::create([
                    'warehouse_id' => $warehouseId,
                    'user_id' => $warehouseManager->id,
                    'name' => ucfirst($category) . " Item {$warehouseId}-{$i}",
                    'description' => "This is a {$category} item for warehouse {$warehouseId}.",
                    'quantity' => $quantity,
                    'min_stock_level' => $minStockLevel,
                    'unit_price' => rand(10, 1000) / 10,
                    'category' => $category,
                    'image' => null,
                ]);
            }
        }
    }
}
