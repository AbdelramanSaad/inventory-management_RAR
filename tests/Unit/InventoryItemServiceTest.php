<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\InventoryItemNotification;
use App\Repositories\InventoryItemRepository;
use App\Repositories\AuditLogRepository;
use App\Services\InventoryItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryItemServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryItemRepository;
    protected $auditLogRepository;
    protected $inventoryItemService;
    protected $admin;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock repositories
        $this->inventoryItemRepository = $this->mock(InventoryItemRepository::class);
        $this->auditLogRepository = $this->mock(AuditLogRepository::class);

        // Create the service with mocked repositories
        $this->inventoryItemService = new InventoryItemService(
            $this->inventoryItemRepository,
            $this->auditLogRepository
        );

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
        Notification::fake();
        Storage::fake('public');

        $file = UploadedFile::fake()->image('item.jpg');

        $itemData = [
            'name' => 'Test Item',
            'description' => 'Test Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'image' => $file,
        ];

        $expectedItem = new InventoryItem([
            'name' => 'Test Item',
            'description' => 'Test Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);
        $expectedItem->id = 1;

        // Set up repository mock expectations
        $this->inventoryItemRepository->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['name'] === 'Test Item' &&
                    $data['description'] === 'Test Description' &&
                    $data['quantity'] === 10 &&
                    $data['min_stock_level'] === 5 &&
                    $data['unit_price'] === 10.99 &&
                    $data['category'] === 'electronics' &&
                    $data['warehouse_id'] === $this->warehouse->id &&
                    $data['user_id'] === $this->admin->id &&
                    !empty($data['image']);
            }))
            ->andReturn($expectedItem);

        // Set up audit log repository mock expectations
        $this->auditLogRepository->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['type'] === 'item_created' &&
                    $data['user_id'] === $this->admin->id &&
                    $data['warehouse_id'] === $this->warehouse->id &&
                    $data['inventory_item_id'] === 1;
            }))
            ->andReturn(true);

        // Call the service method
        $result = $this->inventoryItemService->create($itemData);

        // Verify the result
        $this->assertEquals($expectedItem, $result);

        // Verify notifications were sent
        Notification::assertSentTo(
            [$this->admin],
            InventoryItemNotification::class,
            function ($notification, $channels) {
                return $notification->type === 'item_created';
            }
        );
    }

    /** @test */
    public function it_updates_inventory_item()
    {
        Notification::fake();
        Storage::fake('public');

        $existingItem = InventoryItem::create([
            'name' => 'Original Item',
            'description' => 'Original Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'image' => 'inventory/old-image.jpg',
        ]);

        $updateData = [
            'name' => 'Updated Item',
            'description' => 'Updated Description',
            'quantity' => 10,
        ];

        $updatedItem = clone $existingItem;
        $updatedItem->name = 'Updated Item';
        $updatedItem->description = 'Updated Description';
        $updatedItem->quantity = 10;

        // Set up repository mock expectations
        $this->inventoryItemRepository->shouldReceive('find')
            ->once()
            ->with($existingItem->id)
            ->andReturn($existingItem);

        $this->inventoryItemRepository->shouldReceive('update')
            ->once()
            ->with($existingItem->id, \Mockery::on(function ($data) {
                return $data['name'] === 'Updated Item' &&
                    $data['description'] === 'Updated Description' &&
                    $data['quantity'] === 10;
            }))
            ->andReturn($updatedItem);

        // Set up audit log repository mock expectations
        $this->auditLogRepository->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(function ($data) use ($existingItem) {
                return $data['type'] === 'stock_adjusted' &&
                    $data['user_id'] === $this->admin->id &&
                    $data['warehouse_id'] === $this->warehouse->id &&
                    $data['inventory_item_id'] === $existingItem->id;
            }))
            ->andReturn(true);

        // Call the service method
        $result = $this->inventoryItemService->update($existingItem->id, $updateData);

        // Verify the result
        $this->assertTrue($result);

        // Verify notifications were sent
        Notification::assertSentTo(
            [$this->admin],
            InventoryItemNotification::class,
            function ($notification, $channels) {
                return $notification->type === 'stock_adjusted';
            }
        );
    }

    /** @test */
    public function it_deletes_inventory_item()
    {
        Notification::fake();

        $existingItem = InventoryItem::create([
            'name' => 'Delete Item',
            'description' => 'Delete Description',
            'quantity' => 5,
            'min_stock_level' => 2,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Set up repository mock expectations
        $this->inventoryItemRepository->shouldReceive('find')
            ->once()
            ->with($existingItem->id)
            ->andReturn($existingItem);

        $this->inventoryItemRepository->shouldReceive('delete')
            ->once()
            ->with($existingItem->id)
            ->andReturn(true);

        // Set up audit log repository mock expectations
        $this->auditLogRepository->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(function ($data) use ($existingItem) {
                return $data['type'] === 'item_deleted' &&
                    $data['user_id'] === $this->admin->id &&
                    $data['warehouse_id'] === $this->warehouse->id &&
                    $data['inventory_item_id'] === $existingItem->id;
            }))
            ->andReturn(true);

        // Call the service method
        $result = $this->inventoryItemService->delete($existingItem->id);

        // Verify the result
        $this->assertTrue($result);

        // Verify notifications were sent
        Notification::assertSentTo(
            [$this->admin],
            InventoryItemNotification::class,
            function ($notification, $channels) {
                return $notification->type === 'item_deleted';
            }
        );
    }

    /** @test */
    public function it_sends_low_stock_notification_when_quantity_below_min_level()
    {
        Notification::fake();

        $existingItem = InventoryItem::create([
            'name' => 'Low Stock Item',
            'description' => 'Low Stock Description',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 5.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        $updateData = [
            'quantity' => 3, // Below min_stock_level of 5
        ];

        $updatedItem = clone $existingItem;
        $updatedItem->quantity = 3;

        // Set up repository mock expectations
        $this->inventoryItemRepository->shouldReceive('find')
            ->once()
            ->with($existingItem->id)
            ->andReturn($existingItem);

        $this->inventoryItemRepository->shouldReceive('update')
            ->once()
            ->with($existingItem->id, \Mockery::on(function ($data) {
                return $data['quantity'] === 3;
            }))
            ->andReturn($updatedItem);

        // Set up audit log repository mock expectations
        $this->auditLogRepository->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(function ($data) use ($existingItem) {
                return $data['type'] === 'stock_adjusted' &&
                    $data['user_id'] === $this->admin->id &&
                    $data['warehouse_id'] === $this->warehouse->id &&
                    $data['inventory_item_id'] === $existingItem->id;
            }))
            ->andReturn(true);

        // Call the service method
        $result = $this->inventoryItemService->update($existingItem->id, $updateData);

        // Verify the result
        $this->assertTrue($result);

        // Verify notifications were sent
        Notification::assertSentTo(
            [$this->admin],
            InventoryItemNotification::class,
            function ($notification, $channels) {
                return $notification->type === 'low_stock';
            }
        );
    }
}
