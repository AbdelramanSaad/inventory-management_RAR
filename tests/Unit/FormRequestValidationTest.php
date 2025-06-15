<?php

namespace Tests\Unit;

use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $warehouseManager;
    protected $staff;
    protected $warehouse;
    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'location' => 'Test Location',
        ]);

        // Create users with different roles
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->warehouseManager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse_manager',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Create an inventory item
        $this->inventoryItem = InventoryItem::create([
            'name' => 'Test Item',
            'description' => 'Test Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id,
        ]);
    }

    /** @test */
    public function store_inventory_item_request_validates_correctly()
    {
        // Create a request with valid data
        $validData = [
            'name' => 'New Item',
            'description' => 'New Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
        ];

        // Create a validator instance
        $request = new StoreInventoryItemRequest();
        $validator = Validator::make($validData, $request->rules());

        // Assert validation passes
        $this->assertTrue($validator->passes());

        // Test with invalid data - missing required fields
        $invalidData = [
            'description' => 'New Description',
            'quantity' => 10,
        ];

        $validator = Validator::make($invalidData, $request->rules());

        // Assert validation fails
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('min_stock_level', $validator->errors()->toArray());
        $this->assertArrayHasKey('unit_price', $validator->errors()->toArray());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
        $this->assertArrayHasKey('warehouse_id', $validator->errors()->toArray());

        // Test with invalid data - invalid types
        $invalidData = [
            'name' => 'New Item',
            'description' => 'New Description',
            'quantity' => 'not-a-number',
            'min_stock_level' => 5,
            'unit_price' => 'not-a-number',
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
        ];

        $validator = Validator::make($invalidData, $request->rules());

        // Assert validation fails
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
        $this->assertArrayHasKey('unit_price', $validator->errors()->toArray());
    }

    /** @test */
    public function update_inventory_item_request_validates_correctly()
    {
        // Create a request with valid data
        $validData = [
            'name' => 'Updated Item',
            'description' => 'Updated Description',
            'quantity' => 15,
        ];

        // Create a validator instance
        $request = new UpdateInventoryItemRequest();
        $validator = Validator::make($validData, $request->rules());

        // Assert validation passes
        $this->assertTrue($validator->passes());

        // Test with invalid data - invalid types
        $invalidData = [
            'quantity' => 'not-a-number',
            'min_stock_level' => 'not-a-number',
            'unit_price' => 'not-a-number',
        ];

        $validator = Validator::make($invalidData, $request->rules());

        // Assert validation fails
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
        $this->assertArrayHasKey('min_stock_level', $validator->errors()->toArray());
        $this->assertArrayHasKey('unit_price', $validator->errors()->toArray());
    }

    /** @test */
    public function store_inventory_item_request_authorizes_admin_and_warehouse_manager()
    {
        $request = new StoreInventoryItemRequest();
        
        // Test with admin user
        $this->actingAs($this->admin);
        $this->assertTrue($request->authorize());
        
        // Test with warehouse manager
        $this->actingAs($this->warehouseManager);
        $this->assertTrue($request->authorize());
        
        // Test with staff user
        $this->actingAs($this->staff);
        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function update_inventory_item_request_authorizes_admin_and_warehouse_manager_with_ownership()
    {
        // Mock the route parameter for inventory item ID
        $request = new UpdateInventoryItemRequest();
        $request->route = function () {
            return (object) ['parameter' => function () {
                return $this->inventoryItem->id;
            }];
        };
        
        // Test with admin user
        $this->actingAs($this->admin);
        $this->assertTrue($request->authorize());
        
        // Test with warehouse manager who owns the item
        $this->actingAs($this->warehouseManager);
        $this->assertTrue($request->authorize());
        
        // Create another warehouse manager who doesn't own the item
        $otherWarehouse = Warehouse::create([
            'name' => 'Other Warehouse',
            'location' => 'Other Location',
        ]);
        
        $otherManager = User::create([
            'name' => 'Other Manager',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse_manager',
            'warehouse_id' => $otherWarehouse->id,
        ]);
        
        // Test with warehouse manager who doesn't own the item
        $this->actingAs($otherManager);
        $this->assertFalse($request->authorize());
        
        // Test with staff user
        $this->actingAs($this->staff);
        $this->assertFalse($request->authorize());
    }
}
