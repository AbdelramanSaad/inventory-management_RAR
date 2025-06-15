<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\InventoryItemObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $observer;
    protected $admin;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->observer = new InventoryItemObserver();

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

        // Set the authenticated user
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_creates_audit_log_when_inventory_item_is_created()
    {
        // Create an inventory item
        $item = new InventoryItem([
            'name' => 'Test Item',
            'description' => 'Test Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);
        $item->id = 1; // Simulate ID assignment

        // Call the observer method
        $this->observer->created($item);

        // Check if audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'type' => 'item_created',
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => $item->id,
        ]);
    }

    /** @test */
    public function it_creates_audit_log_when_inventory_item_is_updated()
    {
        // Create an inventory item
        $item = InventoryItem::create([
            'name' => 'Original Item',
            'description' => 'Original Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Update the item
        $item->name = 'Updated Item';
        $item->description = 'Updated Description';

        // Call the observer method
        $this->observer->updated($item);

        // Check if audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'type' => 'item_updated',
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => $item->id,
        ]);
    }

    /** @test */
    public function it_creates_audit_log_when_inventory_item_quantity_is_updated()
    {
        // Create an inventory item
        $item = InventoryItem::create([
            'name' => 'Stock Item',
            'description' => 'Stock Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Save the original quantity
        $originalQuantity = $item->quantity;

        // Update the quantity
        $item->quantity = 10;

        // Set the original attribute to simulate dirty attribute tracking
        $item->setOriginal('quantity', $originalQuantity);

        // Call the observer method
        $this->observer->updated($item);

        // Check if audit log was created with stock_adjusted type
        $this->assertDatabaseHas('audit_logs', [
            'type' => 'stock_adjusted',
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => $item->id,
        ]);

        // Get the audit log and check the description
        $auditLog = AuditLog::where('inventory_item_id', $item->id)
            ->where('type', 'stock_adjusted')
            ->first();

        $this->assertStringContainsString('from 5 to 10', $auditLog->description);
    }

    /** @test */
    public function it_creates_audit_log_when_inventory_item_is_deleted()
    {
        // Create an inventory item
        $item = InventoryItem::create([
            'name' => 'Delete Item',
            'description' => 'Delete Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Call the observer method
        $this->observer->deleted($item);

        // Check if audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'type' => 'item_deleted',
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => $item->id,
        ]);
    }

    /** @test */
    public function it_creates_audit_log_when_inventory_item_is_restored()
    {
        // Create an inventory item
        $item = InventoryItem::create([
            'name' => 'Restore Item',
            'description' => 'Restore Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Call the observer method
        $this->observer->restored($item);

        // Check if audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'type' => 'item_restored',
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => $item->id,
        ]);
    }
}
