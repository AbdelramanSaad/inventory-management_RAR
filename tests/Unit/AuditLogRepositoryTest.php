<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\AuditLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuditLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $auditLogRepository;
    protected $admin;
    protected $warehouse;
    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the repository
        $this->auditLogRepository = new AuditLogRepository();

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
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Set the authenticated user
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_creates_audit_log()
    {
        $logData = [
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Test Item',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ];

        $result = $this->auditLogRepository->create($logData);

        $this->assertInstanceOf(AuditLog::class, $result);
        $this->assertEquals($this->admin->name, $result->user_name);
        $this->assertEquals('item_created', $result->type);
        $this->assertEquals('Created inventory item: Test Item', $result->description);
        $this->assertEquals($this->warehouse->id, $result->warehouse_id);
        $this->assertEquals($this->admin->id, $result->user_id);
        $this->assertEquals($this->inventoryItem->id, $result->inventory_item_id);
    }

    /** @test */
    public function it_gets_filtered_audit_logs()
    {
        // Create multiple audit logs
        AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Test Item',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'stock_adjusted',
            'description' => 'Stock adjusted for Test Item from 5 to 10',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ]);

        // Test filtering by type
        $filters = [
            'type' => 'item_created',
        ];

        $result = $this->auditLogRepository->getFiltered($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('item_created', $result->items()[0]->type);

        // Test filtering by warehouse_id
        $filters = [
            'warehouse_id' => $this->warehouse->id,
        ];

        $result = $this->auditLogRepository->getFiltered($filters);

        $this->assertEquals(2, $result->total());

        // Create a second warehouse and audit log
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'location' => 'Second Location',
        ]);

        AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Second Item',
            'warehouse_id' => $warehouse2->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => null,
            'created_at' => now(),
        ]);

        $filters = [
            'warehouse_id' => $warehouse2->id,
        ];

        $result = $this->auditLogRepository->getFiltered($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals($warehouse2->id, $result->items()[0]->warehouse_id);
    }

    /** @test */
    public function it_caches_filtered_results()
    {
        Cache::flush();

        // Create test audit logs
        AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created inventory item: Test Item',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ]);

        $filters = [
            'type' => 'item_created',
        ];

        // First call should cache the results
        $result1 = $this->auditLogRepository->getFiltered($filters);
        
        // Create another audit log that should not be in the cached results
        AuditLog::create([
            'user_name' => $this->admin->name,
            'type' => 'item_created',
            'description' => 'Created another inventory item',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'created_at' => now(),
        ]);

        // Second call should return cached results
        $result2 = $this->auditLogRepository->getFiltered($filters);

        // The cached results should not include the second log
        $this->assertEquals($result1->total(), $result2->total());
        $this->assertEquals(1, $result2->total());
        
        // Clear cache and verify we get updated results
        $this->auditLogRepository->clearCache();
        
        $result3 = $this->auditLogRepository->getFiltered($filters);
        
        // Now we should see both logs
        $this->assertEquals(2, $result3->total());
    }
}
