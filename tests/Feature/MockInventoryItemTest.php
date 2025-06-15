<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockInventoryItemTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear un almacén de prueba
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'location' => 'Test Location',
        ]);

        // Crear un usuario administrador
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function admin_can_view_all_inventory_items()
    {
        // Crear un elemento de inventario de prueba
        InventoryItem::create([
            'name' => 'Test Item 1',
            'description' => 'Test Description 1',
            'quantity' => 10,
            'min_stock_level' => 5,
            'unit_price' => 10.99,
            'category' => 'electronics',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
        ]);

        // Autenticar al usuario administrador
        $this->actingAs($this->admin);

        // Crear una ruta de prueba que no use middleware JWT
        $response = $this->get('/api/test-inventory-items');

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Test Item 1');
    }
}
