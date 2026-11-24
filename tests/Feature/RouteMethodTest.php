<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteMethodTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function force_change_password_route_accepts_post_and_rejects_put()
    {
        // Get the GET route definition (named route)
        $getRoute = Route::getRoutes()->getByName('password.change');

        $this->assertNotNull($getRoute, 'Route "password.change" should exist');

        // Verify the GET route accepts GET method
        $getMethods = $getRoute->methods();
        $this->assertContains('GET', $getMethods, 'Route "password.change" should accept GET method');

        // Verify the POST route exists for the same URI
        $postRequest = \Illuminate\Http\Request::create('/force-change-password', 'POST');
        $postRoute = Route::getRoutes()->match($postRequest);
        $this->assertNotNull($postRoute, 'POST route for force-change-password should exist');

        $postMethods = $postRoute->methods();
        $this->assertContains('POST', $postMethods, 'POST route for force-change-password should accept POST method');

        // Verify neither route accepts PUT method
        $this->assertNotContains('PUT', $getMethods, 'GET route should NOT accept PUT method');
        $this->assertNotContains('PUT', $postMethods, 'POST route should NOT accept PUT method');
    }

    /** @test */
    public function force_change_password_route_returns_405_for_put_request()
    {
        $response = $this->put(route('password.change'));

        // Should return 405 Method Not Allowed since PUT is not supported
        $this->assertEquals(405, $response->getStatusCode());
    }

    /** @test */
    public function force_change_password_route_works_with_post_request()
    {
        // Create roles if needed
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        // Create a user with must_change_password = true
        $user = \App\Models\User::create([
            'user_id' => 'TEST-0001',
            'email' => 'test@test.edu',
            'password' => bcrypt('password'),
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('student');

        $this->actingAs($user);

        // POST should work (return 200 or redirect, not 405)
        $response = $this->post(route('password.change'), [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Should not be 405
        $this->assertNotEquals(405, $response->getStatusCode(), 'POST should be allowed');
    }

    /** @test */
    public function registrar_student_account_routes_have_correct_methods()
    {
        $routes = [
            'registrar.students.account.store' => 'POST',
            'registrar.students.accounts.bulk' => 'POST',
            'registrar.students.account.reset-password' => 'POST',
            'registrar.students.account.status' => 'PUT',
        ];

        foreach ($routes as $name => $expectedMethod) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} should exist");

            $methods = $route->methods();
            $this->assertContains($expectedMethod, $methods, "Route {$name} should accept {$expectedMethod} method");
        }
    }

    /** @test */
    public function registrar_student_account_status_route_accepts_put_not_post()
    {
        $route = Route::getRoutes()->getByName('registrar.students.account.status');
        $this->assertNotNull($route);

        $methods = $route->methods();
        $this->assertContains('PUT', $methods, 'Account status route should accept PUT');
        $this->assertNotContains('POST', $methods, 'Account status route should NOT accept POST');
    }

    /** @test */
    public function registrar_student_routes_match_controller_actions()
    {
        $routes = [
            'registrar.students.index' => ['GET'],
            'registrar.students.create' => ['GET'],
            'registrar.students.store' => ['POST'],
            'registrar.students.show' => ['GET'],
            'registrar.students.edit' => ['GET'],
            'registrar.students.update' => ['PUT', 'PATCH'],
            'registrar.students.account.store' => ['POST'],
            'registrar.students.accounts.bulk' => ['POST'],
            'registrar.students.account.reset-password' => ['POST'],
            'registrar.students.account.status' => ['PUT'],
        ];

        foreach ($routes as $name => $expectedMethods) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} should exist");

            $methods = $route->methods();
            foreach ($expectedMethods as $method) {
                $this->assertContains($method, $methods, "Route {$name} should accept {$method} method");
            }
        }
    }
}