<?php

namespace Tests\Feature;

use App\Events\InventoryItemEvent;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ApiWebSocketIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $warehouseManager;
    protected $warehouse;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        // Create a warehouse
        $this->warehouse = Warehouse::factory()->create();
        
        // Create warehouse manager for this warehouse
        $this->warehouseManager = User::factory()->create([
            'role' => 'warehouse_manager',
            'warehouse_id' => $this->warehouse->id
        ]);
        
        // Generate JWT token for API authentication
        $this->token = auth()->tokenById($this->admin->id);
    }

    /** @test */
    public function api_can_create_inventory_item_and_broadcast_event()
    {
        Event::fake();
        
        $data = [
            'name' => 'Test Item',
            'description' => 'Test Description',
            'sku' => 'TEST123',
            'quantity' => 10,
            'min_stock_level' => 5,
            'warehouse_id' => $this->warehouse->id,
            'category' => 'electronics',
            'unit_price' => 100.00
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/inventory-items', $data);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id', 'name', 'description', 'sku', 'quantity', 
                         'min_stock_level', 'warehouse_id', 'category', 'unit_price'
                     ],
                     'message'
                 ]);
        
        // Assert that the event was dispatched
        Event::assertDispatched(InventoryItemEvent::class, function ($event) use ($response) {
            $itemId = $response->json('data.id');
            return $event->inventoryItem->id == $itemId &&
                   $event->type === 'item_created';
        });
    }
    
    /** @test */
    public function api_can_update_inventory_item_quantity_and_broadcast_event()
    {
        // Create an inventory item
        $item = InventoryItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'quantity' => 10,
            'min_stock_level' => 5
        ]);
        
        Event::fake();
        
        $data = [
            'quantity' => 20
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->patchJson("/api/inventory-items/{$item->id}", $data);
        
        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $item->id,
                         'quantity' => 20
                     ]
                 ]);
        
        // Assert that the event was dispatched
        Event::assertDispatched(InventoryItemEvent::class, function ($event) use ($item) {
            return $event->inventoryItem->id === $item->id &&
                   $event->type === 'stock_adjusted';
        });
    }
    
    /** @test */
    public function api_can_update_inventory_item_below_min_stock_and_broadcast_low_stock_alert()
    {
        // Create an inventory item
        $item = InventoryItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'quantity' => 10,
            'min_stock_level' => 5
        ]);
        
        Event::fake();
        
        $data = [
            'quantity' => 3 // Below min_stock_level
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->patchJson("/api/inventory-items/{$item->id}", $data);
        
        $response->assertStatus(200);
        
        // Assert that both events were dispatched
        Event::assertDispatched(InventoryItemEvent::class, function ($event) use ($item) {
            return $event->inventoryItem->id === $item->id &&
                   $event->type === 'stock_adjusted';
        });
        
        Event::assertDispatched(InventoryItemEvent::class, function ($event) use ($item) {
            return $event->inventoryItem->id === $item->id &&
                   $event->type === 'low_stock_alert';
        });
    }
    
    /** @test */
    public function api_can_delete_inventory_item_and_broadcast_event()
    {
        // Create an inventory item
        $item = InventoryItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id
        ]);
        
        Event::fake();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/inventory-items/{$item->id}");
        
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Inventory item deleted successfully'
                 ]);
        
        // Assert that the event was dispatched
        Event::assertDispatched(InventoryItemEvent::class, function ($event) use ($item) {
            return $event->inventoryItem->id === $item->id &&
                   $event->type === 'item_deleted';
        });
    }
    
    /** @test */
    public function warehouse_manager_can_only_access_their_warehouse_events()
    {
        // Create another warehouse and item
        $anotherWarehouse = Warehouse::factory()->create();
        $anotherItem = InventoryItem::factory()->create([
            'warehouse_id' => $anotherWarehouse->id,
            'user_id' => $this->admin->id
        ]);
        
        // Generate token for warehouse manager
        $managerToken = auth()->tokenById($this->warehouseManager->id);
        
        // Try to access item from another warehouse
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $managerToken,
            'Accept' => 'application/json'
        ])->getJson("/api/inventory-items/{$anotherItem->id}");
        
        // Should be forbidden
        $response->assertStatus(403);
        
        // Create item in manager's warehouse
        $ownItem = InventoryItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id
        ]);
        
        // Try to access own warehouse item
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $managerToken,
            'Accept' => 'application/json'
        ])->getJson("/api/inventory-items/{$ownItem->id}");
        
        // Should be allowed
        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $ownItem->id,
                         'warehouse_id' => $this->warehouse->id
                     ]
                 ]);
    }
}
