<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventorySystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $warehouseManager;
    protected $staff;
    protected $warehouse;
    protected $token;
    protected $inventoryItemId;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up file storage for testing
        Storage::fake('public');

        // Create a warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Integration Test Warehouse',
            'location' => 'Integration Test Location',
        ]);

        // Create users with different roles
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@integration.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->warehouseManager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@integration.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse_manager',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@integration.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    /** @test */
    public function full_inventory_management_workflow()
    {
        // Step 1: Admin logs in
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@integration.com',
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user',
            ]);

        $this->token = $loginResponse->json('access_token');

        // Step 2: Admin creates a new inventory item
        $image = UploadedFile::fake()->image('product.jpg');

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/inventory-items', [
            'name' => 'Integration Test Item',
            'description' => 'Integration Test Description',
            'quantity' => 20,
            'min_stock_level' => 5,
            'unit_price' => 25.99,
            'category' => 'integration_test',
            'warehouse_id' => $this->warehouse->id,
            'image' => $image,
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'quantity',
                    'min_stock_level',
                    'unit_price',
                    'category',
                    'image',
                    'image_url',
                    'warehouse',
                    'user',
                    'is_low_stock',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->inventoryItemId = $createResponse->json('data.id');

        // Step 3: Admin views the created inventory item
        $viewResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/inventory-items/' . $this->inventoryItemId);

        $viewResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->inventoryItemId,
                    'name' => 'Integration Test Item',
                    'description' => 'Integration Test Description',
                    'quantity' => 20,
                    'min_stock_level' => 5,
                    'unit_price' => 25.99,
                    'category' => 'integration_test',
                ],
            ]);

        // Step 4: Admin logs out
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'message' => 'User successfully signed out',
            ]);

        // Step 5: Warehouse manager logs in
        $managerLoginResponse = $this->postJson('/api/auth/login', [
            'email' => 'manager@integration.com',
            'password' => 'password',
        ]);

        $managerLoginResponse->assertStatus(200);
        $managerToken = $managerLoginResponse->json('access_token');

        // Step 6: Warehouse manager updates the inventory item
        $updateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $managerToken,
        ])->putJson('/api/inventory-items/' . $this->inventoryItemId, [
            'name' => 'Updated Integration Item',
            'quantity' => 10, // Reduce quantity
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Inventory item updated successfully',
            ]);

        // Step 7: Warehouse manager views the updated inventory item
        $viewUpdatedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $managerToken,
        ])->getJson('/api/inventory-items/' . $this->inventoryItemId);

        $viewUpdatedResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->inventoryItemId,
                    'name' => 'Updated Integration Item',
                    'quantity' => 10,
                ],
            ]);

        // Step 8: Warehouse manager checks audit logs
        $auditLogsResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $managerToken,
        ])->getJson('/api/audit-logs?inventory_item_id=' . $this->inventoryItemId);

        $auditLogsResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'description',
                        'user_name',
                        'warehouse',
                        'user',
                        'inventory_item',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        // Verify we have at least 2 audit logs (creation and update)
        $this->assertGreaterThanOrEqual(2, count($auditLogsResponse->json('data')));

        // Step 9: Warehouse manager logs out
        $managerLogoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $managerToken,
        ])->postJson('/api/auth/logout');

        $managerLogoutResponse->assertStatus(200);

        // Step 10: Staff logs in
        $staffLoginResponse = $this->postJson('/api/auth/login', [
            'email' => 'staff@integration.com',
            'password' => 'password',
        ]);

        $staffLoginResponse->assertStatus(200);
        $staffToken = $staffLoginResponse->json('access_token');

        // Step 11: Staff can view inventory items
        $staffViewResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $staffToken,
        ])->getJson('/api/inventory-items/' . $this->inventoryItemId);

        $staffViewResponse->assertStatus(200);

        // Step 12: Staff cannot update inventory items (should be forbidden)
        $staffUpdateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $staffToken,
        ])->putJson('/api/inventory-items/' . $this->inventoryItemId, [
            'name' => 'Staff Should Not Update',
        ]);

        $staffUpdateResponse->assertStatus(403);

        // Step 13: Admin logs in again
        $adminLoginAgainResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@integration.com',
            'password' => 'password',
        ]);

        $adminLoginAgainResponse->assertStatus(200);
        $adminToken = $adminLoginAgainResponse->json('access_token');

        // Step 14: Admin reduces quantity below min_stock_level to trigger low stock
        $lowStockResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->putJson('/api/inventory-items/' . $this->inventoryItemId, [
            'quantity' => 3, // Below min_stock_level of 5
        ]);

        $lowStockResponse->assertStatus(200);

        // Step 15: Admin verifies the item is marked as low stock
        $checkLowStockResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/inventory-items/' . $this->inventoryItemId);

        $checkLowStockResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_low_stock' => true,
                ],
            ]);

        // Step 16: Admin deletes the inventory item
        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->deleteJson('/api/inventory-items/' . $this->inventoryItemId);

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Inventory item deleted successfully',
            ]);

        // Step 17: Admin verifies the item is deleted (should return 404)
        $verifyDeletedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/inventory-items/' . $this->inventoryItemId);

        $verifyDeletedResponse->assertStatus(404);

        // Step 18: Admin checks final audit logs to verify deletion was logged
        $finalAuditResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/audit-logs?inventory_item_id=' . $this->inventoryItemId);

        $finalAuditResponse->assertStatus(200);

        // Verify we have at least 3 audit logs (creation, update, and deletion)
        $this->assertGreaterThanOrEqual(3, count($finalAuditResponse->json('data')));
        
        // Verify the last audit log is for deletion
        $lastAuditLog = $finalAuditResponse->json('data.0'); // Assuming sorted by latest first
        $this->assertEquals('item_deleted', $lastAuditLog['type']);
    }
}
