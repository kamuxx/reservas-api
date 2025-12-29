<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed necessary data
        \App\Models\Role::create(['name' => 'user', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        \App\Models\Status::create(['name' => 'active', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
    }

    public function test_valid_router(): void
    {
        // Assert not 404 (Route exists) and not 405 (Method allowed)
        // We expect 401 because it's protected, but the route itself is valid.
        $response = $this->postJson('/api/auth/logout');
        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(405, $response->status());
    }

    public function test_logout_success(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(204);
    }

    public function test_logout_failed_if_token_not_provided(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_logout_failed_if_token_is_invalid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_token_invalid_after_logout(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = auth('api')->login($user);

        // 1. Logout
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout')
            ->assertStatus(204);

        // 2. Try to use the same token again (e.g., for logout or accessing profile)
        // Should fail because it's blacklisted/invalidated
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}
