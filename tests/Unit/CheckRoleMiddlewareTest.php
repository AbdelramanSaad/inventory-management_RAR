<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckRole();
    }

    /** @test */
    public function it_allows_access_to_users_with_required_role()
    {
        // Create an admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return new Response('Allowed');
        };

        $response = $this->middleware->handle($request, $next, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Allowed', $response->getContent());
    }

    /** @test */
    public function it_allows_access_when_user_has_one_of_multiple_required_roles()
    {
        // Create a warehouse manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse_manager',
        ]);

        $this->actingAs($manager);

        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return new Response('Allowed');
        };

        $response = $this->middleware->handle($request, $next, 'admin,warehouse_manager');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Allowed', $response->getContent());
    }

    /** @test */
    public function it_denies_access_to_users_without_required_role()
    {
        // Create a staff user
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);

        $this->actingAs($staff);

        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return new Response('Allowed');
        };

        $response = $this->middleware->handle($request, $next, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    /** @test */
    public function it_denies_access_to_unauthenticated_users()
    {
        $request = Request::create('/test', 'GET');
        $next = function ($request) {
            return new Response('Allowed');
        };

        $response = $this->middleware->handle($request, $next, 'admin');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Unauthenticated', $response->getContent());
    }
}
