<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use UseCases\UserUseCases;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class RegisterControllerExceptionTest extends TestCase
{
    use RefreshDatabase;

    private $userUseCases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userUseCases = Mockery::mock(UserUseCases::class);
        $this->app->instance(UserUseCases::class, $this->userUseCases);
    }

    public function test_register_throws_pdo_exception(): void
    {
        $this->userUseCases->shouldReceive('registerNewUser')->andThrow(new \PDOException("Database error"));

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '123456789',
        ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Error creating user', $response->json('message'));
        $this->assertStringContainsString('DE', $response->json('message')); // Database Exception prefix
    }

    public function test_register_throws_general_exception(): void
    {
        $this->userUseCases->shouldReceive('registerNewUser')->andThrow(new \Exception("General error"));

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '123456789',
        ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Error creating user', $response->json('message'));
        $this->assertStringContainsString('GE', $response->json('message')); // General Exception prefix
    }
}
