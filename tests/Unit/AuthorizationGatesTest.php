<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationGatesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $warehouseManager;
    protected $staff;
    protected $warehouse;
    protected $otherWarehouse;
    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create warehouses
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'location' => 'Test Location',
        ]);

        $this->otherWarehouse = Warehouse::create([
            'name' => 'Other Warehouse',
            'location' => 'Other Location',
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
    public function admin_actions_gate_allows_only_admin()
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('admin-actions'));
        $this->assertFalse(Gate::forUser($this->warehouseManager)->allows('admin-actions'));
        $this->assertFalse(Gate::forUser($this->staff)->allows('admin-actions'));
    }

    /** @test */
    public function manage_inventory_gate_allows_admin_and_warehouse_managers()
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-inventory'));
        $this->assertTrue(Gate::forUser($this->warehouseManager)->allows('manage-inventory'));
        $this->assertFalse(Gate::forUser($this->staff)->allows('manage-inventory'));
    }

    /** @test */
    public function view_inventory_gate_allows_all_authenticated_users()
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('view-inventory'));
        $this->assertTrue(Gate::forUser($this->warehouseManager)->allows('view-inventory'));
        $this->assertTrue(Gate::forUser($this->staff)->allows('view-inventory'));
    }

    /** @test */
    public function view_warehouse_gate_allows_admin_or_users_belonging_to_warehouse()
    {
        // Admin can view any warehouse
        $this->assertTrue(Gate::forUser($this->admin)->allows('view-warehouse', $this->warehouse));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view-warehouse', $this->otherWarehouse));

        // Warehouse manager can view their own warehouse
        $this->assertTrue(Gate::forUser($this->warehouseManager)->allows('view-warehouse', $this->warehouse));
        $this->assertFalse(Gate::forUser($this->warehouseManager)->allows('view-warehouse', $this->otherWarehouse));

        // Staff can view their own warehouse
        $this->assertTrue(Gate::forUser($this->staff)->allows('view-warehouse', $this->warehouse));
        $this->assertFalse(Gate::forUser($this->staff)->allows('view-warehouse', $this->otherWarehouse));
    }
}
