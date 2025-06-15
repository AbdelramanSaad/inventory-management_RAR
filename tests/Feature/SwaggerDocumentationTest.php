<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SwaggerDocumentationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function it_can_access_swagger_documentation()
    {
        $response = $this->get('/api/documentation');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_access_swagger_json()
    {
        $response = $this->get('/api/documentation/api-docs.json');
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/json');
        
        $content = json_decode($response->getContent(), true);
        
        // Verify basic structure of Swagger JSON
        $this->assertArrayHasKey('openapi', $content);
        $this->assertArrayHasKey('info', $content);
        $this->assertArrayHasKey('paths', $content);
        
        // Verify API info
        $this->assertEquals('Inventory Management and Audit System API', $content['info']['title']);
        
        // Verify that our endpoints are documented
        $this->assertArrayHasKey('/api/inventory-items', $content['paths']);
        $this->assertArrayHasKey('/api/audit-logs', $content['paths']);
    }
    
    /** @test */
    public function it_documents_inventory_item_endpoints()
    {
        $response = $this->get('/api/documentation/api-docs.json');
        $content = json_decode($response->getContent(), true);
        
        // Check inventory items endpoints
        $this->assertArrayHasKey('/api/inventory-items', $content['paths']);
        $this->assertArrayHasKey('get', $content['paths']['/api/inventory-items']);
        $this->assertArrayHasKey('post', $content['paths']['/api/inventory-items']);
        
        // Check inventory item detail endpoints
        $this->assertArrayHasKey('/api/inventory-items/{id}', $content['paths']);
        $this->assertArrayHasKey('get', $content['paths']['/api/inventory-items/{id}']);
        $this->assertArrayHasKey('put', $content['paths']['/api/inventory-items/{id}']);
        $this->assertArrayHasKey('delete', $content['paths']['/api/inventory-items/{id}']);
    }
    
    /** @test */
    public function it_documents_audit_log_endpoints()
    {
        $response = $this->get('/api/documentation/api-docs.json');
        $content = json_decode($response->getContent(), true);
        
        // Check audit logs endpoints
        $this->assertArrayHasKey('/api/audit-logs', $content['paths']);
        $this->assertArrayHasKey('get', $content['paths']['/api/audit-logs']);
        
        // Verify that the audit log endpoint has proper parameters
        $parameters = $content['paths']['/api/audit-logs']['get']['parameters'];
        $paramNames = array_column($parameters, 'name');
        
        $this->assertContains('type', $paramNames);
        $this->assertContains('warehouse_id', $paramNames);
        $this->assertContains('page', $paramNames);
        $this->assertContains('per_page', $paramNames);
    }
    
    /** @test */
    public function it_includes_security_definitions()
    {
        $response = $this->get('/api/documentation/api-docs.json');
        $content = json_decode($response->getContent(), true);
        
        // Check security schemes
        $this->assertArrayHasKey('components', $content);
        $this->assertArrayHasKey('securitySchemes', $content['components']);
        $this->assertArrayHasKey('bearerAuth', $content['components']['securitySchemes']);
        
        // Verify bearer auth is properly defined
        $bearerAuth = $content['components']['securitySchemes']['bearerAuth'];
        $this->assertEquals('http', $bearerAuth['type']);
        $this->assertEquals('bearer', $bearerAuth['scheme']);
        $this->assertEquals('JWT', $bearerAuth['bearerFormat']);
    }
}
