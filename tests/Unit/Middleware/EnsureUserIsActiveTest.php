<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    // parent::setUp() from TestCase handles seeding

    public function test_handle_returns_401_if_user_not_authenticated()
    {
        $middleware = new EnsureUserIsActive();
        $request = Request::create('/test', 'GET');

        // Ensure no user is logged in
        Auth::shouldReceive('user')->once()->andReturn(null);

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"User not found"}', $response->getContent());
    }

    public function test_handle_returns_401_if_user_is_not_active()
    {
        // Must ensure we use 'pending' status.
        // Assuming seeds create 'pending'. If not, create it.
        $pendingStatus = Status::where('name', 'pending')->first();
        if (!$pendingStatus) {
            $pendingStatus = Status::create(['name' => 'pending', 'uuid' => 'pending-uuid']);
        }

        $user = User::factory()->create();
        $user->status_id = $pendingStatus->uuid;
        $user->save();
        $user->refresh();

        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('logout')->once();

        $middleware = new EnsureUserIsActive();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"User is not active"}', $response->getContent());
    }

    public function test_handle_passes_if_user_is_active()
    {
        $user = User::factory()->create();
        // Factory creates active user by default
        $user->refresh();

        Auth::shouldReceive('user')->andReturn($user);
        // Should NOT receive logout

        $middleware = new EnsureUserIsActive();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
