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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Auth\AuthenticationException;

class AuthControllerExceptionTest extends TestCase
{
    use RefreshDatabase;

    private $userUseCases;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userUseCases = Mockery::mock(UserUseCases::class);
        $this->app->instance(UserUseCases::class, $this->userUseCases);
    }

    public function test_activate_throws_not_found_exception(): void
    {
        $this->userUseCases->shouldReceive('activateAccount')->andThrow(new NotFoundHttpException("Not found"));

        $response = $this->postJson('/api/auth/activate', [
            'token' => 'invalid-token',
            'activation_code' => '123456',
        ]);

        $response->assertStatus(404);
        $this->assertStringContainsString('Error al activar la cuenta', $response->json('message'));
        $this->assertStringContainsString('CL', $response->json('message'));
    }

    public function test_activate_throws_unprocessable_exception(): void
    {
        $this->userUseCases->shouldReceive('activateAccount')->andThrow(new UnprocessableEntityHttpException("Unprocessable"));

        $response = $this->postJson('/api/auth/activate', [
            'token' => 'valid-token',
            'activation_code' => '123456',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Error al activar la cuenta', $response->json('message'));
    }

    public function test_activate_throws_general_exception(): void
    {
        $this->userUseCases->shouldReceive('activateAccount')->andThrow(new \Exception("General error"));

        $response = $this->postJson('/api/auth/activate', [
            'token' => 'token',
            'activation_code' => '123456',
        ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al activar la cuenta', $response->json('message'));
    }

    public function test_login_throws_auth_exception(): void
    {
        $this->userUseCases->shouldReceive('login')->andThrow(new AuthenticationException("Invalid credentials"));

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422); // AuthenticationException results in 422 in this controller
        $this->assertStringContainsString('Error al iniciar sesión', $response->json('message'));
    }

    public function test_login_throws_general_exception(): void
    {
        $this->userUseCases->shouldReceive('login')->andThrow(new \Exception("General error"));

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al iniciar sesión', $response->json('message'));
    }

    public function test_logout_throws_exception(): void
    {
        $roleUser = Role::where('name', 'user')->first() ?: Role::create(['name' => 'user', 'uuid' => (string) Str::uuid()]);
        $statusActive = Status::where('name', 'active')->first() ?: Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);
        $user = User::factory()->create(['role_id' => $roleUser->uuid, 'status_id' => $statusActive->uuid]);

        $this->userUseCases->shouldReceive('logout')->andThrow(new \Exception("Logout error"));

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/auth/logout');

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al cerrar sesión', $response->json('message'));
    }
}
