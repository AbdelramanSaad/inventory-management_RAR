<?php

namespace Tests\Unit;

use App\Http\Resources\AuditLogResource;
use App\Http\Resources\InventoryItemResource;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $warehouse;
    protected $inventoryItem;
    protected $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'location' => 'Test Location',
        ]);

        // Create an admin user
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create an inventory item
        $this->inventoryItem = InventoryItem::create([
            'name' => 'Test Item',
            'description' => 'Test Description',
            'quantity' => 3,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'image' => 'inventory/test-image.jpg',
        ]);

        // Create an audit log
        $this->auditLog = AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Test Item',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ]);
    }

    /** @test */
    public function inventory_item_resource_formats_correctly()
    {
        $resource = new InventoryItemResource($this->inventoryItem);
        $resourceArray = $resource->toArray(request());

        $this->assertEquals($this->inventoryItem->id, $resourceArray['id']);
        $this->assertEquals($this->inventoryItem->name, $resourceArray['name']);
        $this->assertEquals($this->inventoryItem->description, $resourceArray['description']);
        $this->assertEquals($this->inventoryItem->quantity, $resourceArray['quantity']);
        $this->assertEquals($this->inventoryItem->min_stock_level, $resourceArray['min_stock_level']);
        $this->assertEquals($this->inventoryItem->unit_price, $resourceArray['unit_price']);
        $this->assertEquals($this->inventoryItem->category, $resourceArray['category']);
        $this->assertEquals($this->inventoryItem->image, $resourceArray['image']);
        $this->assertEquals(asset('storage/' . $this->inventoryItem->image), $resourceArray['image_url']);
        
        // Check warehouse data
        $this->assertEquals($this->warehouse->id, $resourceArray['warehouse']['id']);
        $this->assertEquals($this->warehouse->name, $resourceArray['warehouse']['name']);
        $this->assertEquals($this->warehouse->location, $resourceArray['warehouse']['location']);
        
        // Check user data
        $this->assertEquals($this->admin->id, $resourceArray['user']['id']);
        $this->assertEquals($this->admin->name, $resourceArray['user']['name']);
        
        // Check low stock status
        $this->assertTrue($resourceArray['is_low_stock']);
        $this->assertArrayHasKey('created_at', $resourceArray);
        $this->assertArrayHasKey('updated_at', $resourceArray);
    }

    /** @test */
    public function audit_log_resource_formats_correctly()
    {
        $resource = new AuditLogResource($this->auditLog);
        $resourceArray = $resource->toArray(request());

        $this->assertEquals($this->auditLog->id, $resourceArray['id']);
        $this->assertEquals($this->auditLog->type, $resourceArray['type']);
        $this->assertEquals($this->auditLog->description, $resourceArray['description']);
        $this->assertEquals($this->auditLog->user_name, $resourceArray['user_name']);
        
        // Check warehouse data
        $this->assertEquals($this->warehouse->id, $resourceArray['warehouse']['id']);
        $this->assertEquals($this->warehouse->name, $resourceArray['warehouse']['name']);
        $this->assertEquals($this->warehouse->location, $resourceArray['warehouse']['location']);
        
        // Check user data
        $this->assertEquals($this->admin->id, $resourceArray['user']['id']);
        $this->assertEquals($this->admin->name, $resourceArray['user']['name']);
        
        // Check inventory item data
        $this->assertEquals($this->inventoryItem->id, $resourceArray['inventory_item']['id']);
        $this->assertEquals($this->inventoryItem->name, $resourceArray['inventory_item']['name']);
        
        $this->assertArrayHasKey('created_at', $resourceArray);
    }

    /** @test */
    public function inventory_item_resource_handles_null_relationships()
    {
        // Create an inventory item without a user
        $itemWithoutUser = InventoryItem::create([
            'name' => 'No User Item',
            'description' => 'No User Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 15.99,
            'category' => 'furniture',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => null,
        ]);

        $resource = new InventoryItemResource($itemWithoutUser);
        $resourceArray = $resource->toArray(request());

        $this->assertEquals($itemWithoutUser->id, $resourceArray['id']);
        $this->assertEquals($itemWithoutUser->name, $resourceArray['name']);
        $this->assertNull($resourceArray['user']);
    }

    /** @test */
    public function audit_log_resource_handles_null_relationships()
    {
        // Create an audit log without an inventory item
        $logWithoutItem = AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'system_event',
            'description' => 'System maintenance completed',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => null,
            'created_at' => now(),
        ]);

        $resource = new AuditLogResource($logWithoutItem);
        $resourceArray = $resource->toArray(request());

        $this->assertEquals($logWithoutItem->id, $resourceArray['id']);
        $this->assertEquals($logWithoutItem->type, $resourceArray['type']);
        $this->assertEquals($logWithoutItem->description, $resourceArray['description']);
        $this->assertNull($resourceArray['inventory_item']);
    }
}
