<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_valid_router(): void
    {
        $response = $this->post('/api/auth/login');

        $this->assertNotEquals($response->status(), 404);
    }

    public function test_login_success(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Tost@123',
        ]);

        $response->assertStatus(200);
    }

    public function test_login_failed_by_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $content = $response->json();
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('Las credenciales son incorrectas', $content['message']);
    }

    public function test_login_failed_by_invalid_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $content = $response->json();
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('El correo electrónico no es válido', $content['message']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Seed necessary data for tests
        \App\Models\Role::create(['name' => 'user', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        \App\Models\Role::create(['name' => 'admin', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        \App\Models\Status::create(['name' => 'active', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        \App\Models\Status::create(['name' => 'pending', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        \App\Models\Status::create(['name' => 'blocked', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
    }

    public function test_login_failed_by_invalid_password_format(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password', // Invalid format (regex fail)
        ]);

        $response->assertStatus(422);
        $content = $response->json();
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('Las credenciales son incorrectas', $content['message']);
    }

    public function test_login_failed_by_password_too_short(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'pass', // Invalid format (regex fail)
        ]);

        $response->assertStatus(422);
        $content = $response->json();
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('La contraseña debe tener', $content['message']);
    }

    public function test_login_failed_by_wrong_password(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'WrongP@ssw0rd!',
        ]);

        // Current implementation validation throws 422 for isValidPassword check
        $response->assertStatus(422);
    }

    public function test_login_failed_by_inactive_user(): void
    {
        // Create a user with pending status
        $pendingStatus = \App\Models\Status::where('name', 'pending')->first();
        $user = User::factory()->create([
            'status_id' => $pendingStatus->uuid
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Tost@123',
        ]);

        $response->assertStatus(422);
        $content = $response->json();
        $this->assertStringContainsString('El usuario no esta activo', $content['message']);
    }
    public function test_login_failed_by_blocked_user(): void
    {
        // Create a user with pending status
        $blockedStatus = \App\Models\Status::where('name', 'blocked')->first();
        $user = User::factory()->create([
            'status_id' => $blockedStatus->uuid
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Tost@123',
        ]);

        $response->assertStatus(422);
        $content = $response->json();
        $this->assertStringContainsString('El usuario no esta activo', $content['message']);
    }
}
