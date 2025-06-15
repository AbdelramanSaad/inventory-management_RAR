<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuditLogControllerTest extends TestCase
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

        // Create audit logs
        AuditLog::create([
            'user_name' => $this->warehouseManager->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Test Item',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_view_all_audit_logs()
    {
        // Create a second warehouse
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'location' => 'Second Location',
        ]);

        // Create an item in the second warehouse
        $item2 = InventoryItem::create([
            'name' => 'Second Item',
            'description' => 'Second Description',
            'quantity' => 20,
            'min_stock_level' => 10,
            'unit_price' => 20.99,
            'category' => 'furniture',
            'warehouse_id' => $warehouse2->id,
            'user_id' => $this->admin->id,
        ]);

        // Create audit log for the second warehouse
        AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Second Item',
            'warehouse_id' => $warehouse2->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $item2->id,
            'created_at' => now(),
        ]);

        $token = JWTAuth::fromUser($this->admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function warehouse_manager_can_only_view_their_warehouse_audit_logs()
    {
        // Create a second warehouse
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'location' => 'Second Location',
        ]);

        // Create an item in the second warehouse
        $item2 = InventoryItem::create([
            'name' => 'Second Item',
            'description' => 'Second Description',
            'quantity' => 20,
            'min_stock_level' => 10,
            'unit_price' => 20.99,
            'category' => 'furniture',
            'warehouse_id' => $warehouse2->id,
            'user_id' => $this->admin->id,
        ]);

        // Create audit log for the second warehouse
        AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Second Item',
            'warehouse_id' => $warehouse2->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $item2->id,
            'created_at' => now(),
        ]);

        $token = JWTAuth::fromUser($this->warehouseManager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.warehouse.id', $this->warehouse->id);
    }

    /** @test */
    public function staff_can_view_their_warehouse_audit_logs()
    {
        $token = JWTAuth::fromUser($this->staff);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.warehouse.id', $this->warehouse->id);
    }

    /** @test */
    public function can_filter_audit_logs_by_type()
    {
        // Create another audit log with different type
        AuditLog::create([
            'user_name' => $this->warehouseManager->name,
            'type' => 'stock_adjusted',
            'description' => 'Stock adjusted for Test Item from 5 to 10',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->warehouseManager->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ]);

        $token = JWTAuth::fromUser($this->admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/audit-logs?type=stock_adjusted');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'stock_adjusted');
    }
}
