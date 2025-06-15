<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class InventoryItemControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $warehouseManager;
    protected $staff;
    protected $warehouse;

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
            'name' => 'Warehouse Manager',
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
    }

    /** @test */
    public function admin_can_view_all_inventory_items()
    {
        // Create some inventory items
        InventoryItem::create([
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id,
        ]);

        $token = JWTAuth::fromUser($this->admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/inventory-items');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Test Item 1');
    }

    /** @test */
    public function warehouse_manager_can_only_view_their_warehouse_items()
    {
        // Create a second warehouse and item
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'location' => 'Second Location',
        ]);

        // Create item in the manager's warehouse
        $item1 = InventoryItem::create([
            'name' => 'Manager Item',
            'description' => 'Manager Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id,
        ]);

        // Create item in another warehouse
        $item2 = InventoryItem::create([
            'name' => 'Other Item',
            'description' => 'Other Description',
            'quantity' => 20,
            'min_stock_level' => 10,
            'unit_price' => 20.99,
            'category' => 'furniture',
            'warehouse_id' => $warehouse2->id,
            'user_id' => $this->admin->id,
        ]);

        $token = JWTAuth::fromUser($this->warehouseManager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/inventory-items');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Manager Item');
    }

    /** @test */
    public function warehouse_manager_can_create_inventory_item()
    {
        Storage::fake('public');
        
        $token = JWTAuth::fromUser($this->warehouseManager);
        
        $file = UploadedFile::fake()->image('item.jpg');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/inventory-items', [
            'name' => 'New Item',
            'description' => 'New Description',
            'quantity' => 15,
            'min_stock_level' => 5,
            'unit_price' => 25.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'image' => $file,
        ]);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'New Item',
            'description' => 'New Description',
            'quantity' => 15,
            'min_stock_level' => 5,
            'unit_price' => 25.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    /** @test */
    public function staff_cannot_create_inventory_item()
    {
        $token = JWTAuth::fromUser($this->staff);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/inventory-items', [
            'name' => 'Staff Item',
            'description' => 'Staff Description',
            'quantity' => 15,
            'min_stock_level' => 5,
            'unit_price' => 25.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
        ]);
        
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_inventory_item()
    {
        $item = InventoryItem::create([
            'name' => 'Delete Item',
            'description' => 'Delete Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id,
        ]);

        $token = JWTAuth::fromUser($this->admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/inventory-items/' . $item->id);

        $response->assertStatus(200);
        
        // Check that the item is soft deleted
        $this->assertSoftDeleted('inventory_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function warehouse_manager_cannot_delete_inventory_item()
    {
        $item = InventoryItem::create([
            'name' => 'Manager Delete Item',
            'description' => 'Manager Delete Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id,
        ]);

        $token = JWTAuth::fromUser($this->warehouseManager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/inventory-items/' . $item->id);

        $response->assertStatus(403);
    }
}
