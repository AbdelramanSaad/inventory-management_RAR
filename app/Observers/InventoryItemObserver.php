<?php

namespace App\Observers;

use App\Models\InventoryItem;
use App\Models\AuditLog;
use App\Events\InventoryItemEvent;
use App\Notifications\InventoryItemNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class InventoryItemObserver
{
    /**
     * Handle the InventoryItem "created" event.
     */
    public function created(InventoryItem $inventoryItem): void
    {
        $user = Auth::user();
        
        if ($user) {
            // Create audit log
            AuditLog::create([
                'user_name' => $user->name,
                'type' => 'item_created',
                'description' => "Created inventory item: {$inventoryItem->name}",
                'warehouse_id' => $inventoryItem->warehouse_id,
                'user_id' => $user->id,
                'inventory_item_id' => $inventoryItem->id,
                'created_at' => now(),
            ]);
            
            // Send real-time notification via WebSockets
            event(new InventoryItemEvent(
                $inventoryItem,
                'item_created',
                "Created inventory item: {$inventoryItem->name}"
            ));
            
            // Send notification to warehouse managers
            $this->notifyWarehouseManagers($inventoryItem, 'item_created');
        }
    }

    /**
     * Handle the InventoryItem "updated" event.
     */
    public function updated(InventoryItem $inventoryItem): void
    {
        $user = Auth::user();
        
        if ($user) {
            // Check if quantity was changed
            if ($inventoryItem->isDirty('quantity')) {
                $oldQuantity = $inventoryItem->getOriginal('quantity');
                $message = "Stock adjusted for {$inventoryItem->name} from {$oldQuantity} to {$inventoryItem->quantity}";
                
                // Create audit log
                AuditLog::create([
                    'user_name' => $user->name,
                    'type' => 'stock_adjusted',
                    'description' => $message,
                    'warehouse_id' => $inventoryItem->warehouse_id,
                    'user_id' => $user->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'created_at' => now(),
                ]);
                
                // Send real-time notification via WebSockets
                event(new InventoryItemEvent(
                    $inventoryItem,
                    'stock_adjusted',
                    $message
                ));
                
                // Check for low stock and notify if needed
                if ($inventoryItem->isLowStock()) {
                    event(new InventoryItemEvent(
                        $inventoryItem,
                        'low_stock_alert',
                        "Low stock alert for {$inventoryItem->name}: {$inventoryItem->quantity} items remaining"
                    ));
                    
                    // Send notification to warehouse managers
                    $this->notifyWarehouseManagers($inventoryItem, 'low_stock');
                }
            } else {
                $message = "Updated inventory item: {$inventoryItem->name}";
                
                // Create audit log
                AuditLog::create([
                    'user_name' => $user->name,
                    'type' => 'item_updated',
                    'description' => $message,
                    'warehouse_id' => $inventoryItem->warehouse_id,
                    'user_id' => $user->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'created_at' => now(),
                ]);
                
                // Send real-time notification via WebSockets
                event(new InventoryItemEvent(
                    $inventoryItem,
                    'item_updated',
                    $message
                ));
                
                // Send notification to warehouse managers
                $this->notifyWarehouseManagers($inventoryItem, 'item_updated');
            }
        }
    }

    /**
     * Handle the InventoryItem "deleted" event.
     */
    public function deleted(InventoryItem $inventoryItem): void
    {
        $user = Auth::user();
        
        if ($user) {
            $message = "Deleted inventory item: {$inventoryItem->name}";
            
            // Create audit log
            AuditLog::create([
                'user_name' => $user->name,
                'type' => 'item_deleted',
                'description' => $message,
                'warehouse_id' => $inventoryItem->warehouse_id,
                'user_id' => $user->id,
                'inventory_item_id' => $inventoryItem->id,
                'created_at' => now(),
            ]);
            
            // Send real-time notification via WebSockets
            event(new InventoryItemEvent(
                $inventoryItem,
                'item_deleted',
                $message
            ));
            
            // Send notification to warehouse managers
            $this->notifyWarehouseManagers($inventoryItem, 'item_deleted');
        }
    }
    
    /**
     * Send notifications to warehouse managers
     *
     * @param InventoryItem $inventoryItem
     * @param string $type
     * @return void
     */
    private function notifyWarehouseManagers(InventoryItem $inventoryItem, string $type): void
    {
        // Get warehouse managers for this warehouse
        $warehouseManagers = $inventoryItem->warehouse->users()->where('role', 'warehouse_manager')->get();
        
        // Send notification to all warehouse managers
        Notification::send($warehouseManagers, new InventoryItemNotification($inventoryItem, $type, 
            $type === 'low_stock' ? "Low stock alert for {$inventoryItem->name}: {$inventoryItem->quantity} items remaining" : "Inventory item {$inventoryItem->name} has been {$type}"
        ));
    }
}
