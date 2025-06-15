<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\InventoryItemRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InventoryItemRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryItemRepository;
    protected $admin;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the repository
        $this->inventoryItemRepository = new InventoryItemRepository();

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
    public function it_creates_inventory_item()
    {
        $itemData = [
            'name' => 'Test Item',
            'description' => 'Test Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ];

        $result = $this->inventoryItemRepository->create($itemData);

        $this->assertInstanceOf(InventoryItem::class, $result);
        $this->assertEquals('Test Item', $result->name);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals(10, $result->quantity);
        $this->assertEquals(5, $result->min_stock_level);
        $this->assertEquals(10.99, $result->unit_price);
        $this->assertEquals('electronics', $result->category);
        $this->assertEquals($this->warehouse->id, $result->warehouse_id);
        $this->assertEquals($this->admin->id, $result->user_id);
    }

    /** @test */
    public function it_updates_inventory_item()
    {
        // Create an item
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

        $updateData = [
            'name' => 'Updated Item',
            'description' => 'Updated Description',
            'quantity' => 10,
        ];

        $result = $this->inventoryItemRepository->update($item->id, $updateData);

        $this->assertTrue($result);
        
        // Refresh the item from the database
        $updatedItem = InventoryItem::find($item->id);
        $this->assertEquals('Updated Item', $updatedItem->name);
        $this->assertEquals('Updated Description', $updatedItem->description);
        $this->assertEquals(10, $updatedItem->quantity);
        $this->assertEquals(2, $updatedItem->min_stock_level); // Unchanged
    }

    /** @test */
    public function it_deletes_inventory_item()
    {
        // Create an item
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

        $result = $this->inventoryItemRepository->delete($item->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
    }

    /** @test */
    public function it_finds_inventory_item_by_id()
    {
        // Create an item
        $item = InventoryItem::create([
            'name' => 'Find Item',
            'description' => 'Find Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        $result = $this->inventoryItemRepository->find($item->id);

        $this->assertInstanceOf(InventoryItem::class, $result);
        $this->assertEquals($item->id, $result->id);
        $this->assertEquals('Find Item', $result->name);
    }

    /** @test */
    public function it_gets_filtered_inventory_items()
    {
        // Create multiple items
        InventoryItem::create([
            'name' => 'Electronics Item',
            'description' => 'Electronics Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        InventoryItem::create([
            'name' => 'Furniture Item',
            'description' => 'Furniture Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 15.99,
            'category' => 'furniture',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Test filtering by category
        $filters = [
            'category' => 'electronics',
        ];

        $result = $this->inventoryItemRepository->getFiltered($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Electronics Item', $result->items()[0]->name);

        // Test filtering by below_min_stock
        $filters = [
            'below_min_stock' => true,
        ];

        $result = $this->inventoryItemRepository->getFiltered($filters);

        $this->assertEquals(0, $result->total()); // No items are below min stock level

        // Create an item below min stock level
        InventoryItem::create([
            'name' => 'Low Stock Item',
            'description' => 'Low Stock Description',
            'quantity' => 1,
            'min_stock_level' => 5,
            'unit_price' => 7.99,
            'category' => 'clothing',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        $result = $this->inventoryItemRepository->getFiltered($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Low Stock Item', $result->items()[0]->name);

        // Test search filter
        $filters = [
            'search' => 'furniture',
        ];

        $result = $this->inventoryItemRepository->getFiltered($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Furniture Item', $result->items()[0]->name);
    }

    /** @test */
    public function it_caches_filtered_results()
    {
        Cache::flush();

        // Create test items
        InventoryItem::create([
            'name' => 'Cache Test Item',
            'description' => 'Cache Test Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        $filters = [
            'category' => 'electronics',
        ];

        // First call should cache the results
        $result1 = $this->inventoryItemRepository->getFiltered($filters);
        
        // Create another item that should not be in the cached results
        InventoryItem::create([
            'name' => 'Second Cache Test Item',
            'description' => 'Second Cache Test Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 15.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Second call should return cached results
        $result2 = $this->inventoryItemRepository->getFiltered($filters);

        // The cached results should not include the second item
        $this->assertEquals($result1->total(), $result2->total());
        $this->assertEquals(1, $result2->total());
        
        // Clear cache and verify we get updated results
        $this->inventoryItemRepository->clearCache();
        
        $result3 = $this->inventoryItemRepository->getFiltered($filters);
        
        // Now we should see both items
        $this->assertEquals(2, $result3->total());
    }
}
