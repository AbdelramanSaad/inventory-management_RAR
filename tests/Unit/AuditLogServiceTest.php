<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\AuditLogRepository;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $auditLogRepository;
    protected $auditLogService;
    protected $admin;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock repository
        $this->auditLogRepository = $this->mock(AuditLogRepository::class);

        // Create the service with mocked repository
        $this->auditLogService = new AuditLogService($this->auditLogRepository);

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
    public function it_gets_filtered_audit_logs()
    {
        // Create mock audit logs
        $auditLogs = collect([
            new AuditLog([
                'id' => 1,
                'user_name' => 'Admin User',
                'type' => 'item_created',
                'description' => 'Created inventory item: Test Item',
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->admin->id,
                'inventory_item_id' => 1,
                'created_at' => now(),
            ]),
            new AuditLog([
                'id' => 2,
                'user_name' => 'Admin User',
                'type' => 'stock_adjusted',
                'description' => 'Stock adjusted for Test Item from 5 to 10',
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->admin->id,
                'inventory_item_id' => 1,
                'created_at' => now(),
            ]),
        ]);

        // Create a paginator with the mock audit logs
        $paginator = new LengthAwarePaginator(
            $auditLogs,
            2,
            15,
            1
        );

        // Define filters
        $filters = [
            'type' => 'item_created',
            'warehouse_id' => $this->warehouse->id,
        ];

        // Set up repository mock expectations
        $this->auditLogRepository->shouldReceive('getFiltered')
            ->once()
            ->with($filters, 15)
            ->andReturn($paginator);

        // Call the service method
        $result = $this->auditLogService->getFiltered($filters, 15);

        // Verify the result
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
        $this->assertEquals(2, $result->count());
    }

    /** @test */
    public function it_gets_filtered_audit_logs_with_default_pagination()
    {
        // Create mock audit logs
        $auditLogs = collect([
            new AuditLog([
                'id' => 1,
                'user_name' => 'Admin User',
                'type' => 'item_created',
                'description' => 'Created inventory item: Test Item',
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->admin->id,
                'inventory_item_id' => 1,
                'created_at' => now(),
            ]),
        ]);

        // Create a paginator with the mock audit logs
        $paginator = new LengthAwarePaginator(
            $auditLogs,
            1,
            10,
            1
        );

        // Define filters
        $filters = [
            'warehouse_id' => $this->warehouse->id,
        ];

        // Set up repository mock expectations
        $this->auditLogRepository->shouldReceive('getFiltered')
            ->once()
            ->with($filters, 10)
            ->andReturn($paginator);

        // Call the service method
        $result = $this->auditLogService->getFiltered($filters);

        // Verify the result
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals(1, $result->count());
    }
}
