<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\InventoryItemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InventoryItemNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $warehouseManager;
    protected $warehouse;
    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

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
    }

    /** @test */
    public function it_sends_item_created_notification()
    {
        // Create notification instance
        $notification = new InventoryItemNotification(
            $this->inventoryItem,
            'item_created',
            'Inventory item Test Item has been created'
        );

        // Send notification
        $this->admin->notify($notification);

        // Assert notification was sent
        Notification::assertSentTo(
            $this->admin,
            InventoryItemNotification::class,
            function ($notification) {
                return $notification->type === 'item_created' &&
                    $notification->inventoryItem->id === $this->inventoryItem->id;
            }
        );
    }

    /** @test */
    public function it_sends_stock_adjusted_notification()
    {
        // Create notification instance
        $notification = new InventoryItemNotification(
            $this->inventoryItem,
            'stock_adjusted',
            'Stock for Test Item has been adjusted from 5 to 10'
        );

        // Send notification to warehouse manager
        $this->warehouseManager->notify($notification);

        // Assert notification was sent
        Notification::assertSentTo(
            $this->warehouseManager,
            InventoryItemNotification::class,
            function ($notification) {
                return $notification->type === 'stock_adjusted' &&
                    $notification->inventoryItem->id === $this->inventoryItem->id;
            }
        );
    }

    /** @test */
    public function it_sends_low_stock_notification()
    {
        // Update inventory item to low stock
        $this->inventoryItem->quantity = 3; // Below min_stock_level of 5
        $this->inventoryItem->save();

        // Create notification instance
        $notification = new InventoryItemNotification(
            $this->inventoryItem,
            'low_stock',
            'Low stock alert for Test Item. Current quantity: 3, Minimum level: 5'
        );

        // Send notification to both admin and warehouse manager
        $this->admin->notify($notification);
        $this->warehouseManager->notify($notification);

        // Assert notification was sent to both users
        Notification::assertSentTo(
            [$this->admin, $this->warehouseManager],
            InventoryItemNotification::class,
            function ($notification) {
                return $notification->type === 'low_stock' &&
                    $notification->inventoryItem->id === $this->inventoryItem->id;
            }
        );
    }

    /** @test */
    public function it_sends_item_deleted_notification()
    {
        // Create notification instance
        $notification = new InventoryItemNotification(
            $this->inventoryItem,
            'item_deleted',
            'Inventory item Test Item has been deleted'
        );

        // Send notification
        $this->admin->notify($notification);

        // Assert notification was sent
        Notification::assertSentTo(
            $this->admin,
            InventoryItemNotification::class,
            function ($notification) {
                return $notification->type === 'item_deleted' &&
                    $notification->inventoryItem->id === $this->inventoryItem->id;
            }
        );
    }

    /** @test */
    public function notification_contains_correct_data_for_mail_channel()
    {
        // Create notification instance
        $notification = new InventoryItemNotification(
            $this->inventoryItem,
            'low_stock',
            'Low stock alert for Test Item. Current quantity: 3, Minimum level: 5'
        );

        // Get mail representation
        $mail = $notification->toMail($this->admin);

        // Assert mail contains correct data
        $this->assertEquals('Inventory Alert: Low Stock', $mail->subject);
        $this->assertStringContainsString('Low stock alert for Test Item', implode(' ', $mail->introLines));
    }

    /** @test */
    public function notification_contains_correct_data_for_database_channel()
    {
        // Create notification instance
        $notification = new InventoryItemNotification(
            $this->inventoryItem,
            'item_created',
            'Inventory item Test Item has been created'
        );

        // Get database representation
        $array = $notification->toArray($this->admin);

        // Assert database notification contains correct data
        $this->assertEquals('item_created', $array['type']);
        $this->assertEquals('Inventory item Test Item has been created', $array['message']);
        $this->assertEquals($this->inventoryItem->id, $array['inventory_item_id']);
        $this->assertEquals($this->warehouse->id, $array['warehouse_id']);
    }
}
