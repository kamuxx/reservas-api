<?php

namespace Tests\Unit\UseCases;

use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\UseCases\UserUseCases;
use App\Repositories\UserRepository;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserUseCasesTest extends TestCase
{
    use RefreshDatabase;

    private $userRepository;
    private $userUseCases;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userUseCases = new UserUseCases($this->userRepository);
        
        // Ensure roles and statuses exist for factories
        if (!\App\Models\Role::where('name', 'user')->exists()) {
            \App\Models\Role::create(['name' => 'user', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }
        if (!\App\Models\Status::where('name', 'active')->exists()) {
            \App\Models\Status::create(['name' => 'active', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }
    }

    public function test_register_new_user_success(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $user = new User($data);
        $this->userRepository->expects($this->once())
            ->method('create')
            ->with($data)
            ->willReturn($user);

        $result = $this->userUseCases->register($data);
        $this->assertEquals($user, $result);
    }

    public function test_register_new_user_throws_exception_on_failure(): void
    {
        $data = ['name' => 'Test User'];

        $this->userRepository->expects($this->once())
            ->method('create')
            ->with($data)
            ->willThrowException(new \Exception("Error creating user"));

        $this->expectException(\Exception::class);
        $this->userUseCases->register($data);
    }

    public function test_activate_account_success(): void
    {
        $token = 'valid-token';
        $user = new User(['email' => 'test@example.com']);

        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('activate')
            ->with($user);

        $result = $this->userUseCases->activateAccount($token);
        $this->assertEquals($user, $result);
    }

    public function test_activate_account_token_not_found(): void
    {
        $token = 'invalid-token';

        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token no encontrado");
        $this->userUseCases->activateAccount($token);
    }

    public function test_activate_account_token_expired(): void
    {
        $token = 'expired-token';
        $user = new User([
            'email_verification_token' => $token,
            'email_verification_expires_at' => now()->subDay()
        ]);

        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token expirado");
        $this->userUseCases->activateAccount($token);
    }

    public function test_activate_account_token_used(): void
    {
        $token = 'used-token';
        $user = new User([
            'email_verified_at' => now(),
            'email_verification_token' => $token
        ]);

        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token ya utilizado");
        $this->userUseCases->activateAccount($token);
    }

    public function test_activate_account_invalid_code(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token inválido");
        $this->userUseCases->activateAccount('');
    }

    public function test_activate_account_user_already_active(): void
    {
        $token = 'valid-token';
        $user = new User([
            'email_verified_at' => now(),
            'email_verification_token' => $token
        ]);

        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Usuario ya activo");
        $this->userUseCases->activateAccount($token);
    }

    public function test_login_success(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];
        $user = new User($credentials);
        $token = 'jwt-token';

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($credentials['email'])
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('updateLastLogin')
            ->with($user);

        // Mock JWT token generation
        auth('api')->shouldReceive('attempt')
            ->once()
            ->with([$credentials['email'], $credentials['password']])
            ->andReturn(true);

        $result = $this->userUseCases->login($credentials);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
    }

    public function test_login_user_not_found(): void
    {
        $credentials = ['email' => 'notfound@example.com', 'password' => 'password'];

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($credentials['email'])
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Credenciales inválidas");
        $this->userUseCases->login($credentials);
    }

    public function test_login_user_inactive(): void
    {
        $credentials = ['email' => 'inactive@example.com', 'password' => 'password'];
        $user = new User(['is_active' => false]);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($credentials['email'])
            ->willReturn($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Usuario inactivo");
        $this->userUseCases->login($credentials);
    }

    public function test_login_invalid_password(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'wrong'];
        $user = new User(['email' => 'test@example.com']);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($credentials['email'])
            ->willReturn($user);

        auth('api')->shouldReceive('attempt')
            ->once()
            ->with([$credentials['email'], $credentials['password']])
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Credenciales inválidas");
        $this->userUseCases->login($credentials);
    }

    public function test_login_attempt_fails(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error en el login");
        $this->userUseCases->login([]);
    }

    public function test_register_new_user_notify_fails(): void
    {
        $data = ['name' => 'Test User'];

        $this->userRepository->expects($this->once())
            ->method('create')
            ->with($data)
            ->willThrowException(new \Exception("Notification failed"));

        $this->expectException(\Exception::class);
        $this->userUseCases->register($data);
    }

    public function test_register_new_user_invalid_return(): void
    {
        $data = ['name' => 'Test User'];

        $this->userRepository->expects($this->once())
            ->method('create')
            ->with($data)
            ->willThrowException(new \Exception("Invalid return"));

        $this->expectException(\Exception::class);
        $this->userUseCases->register($data);
    }

    public function test_activate_account_general_exception(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->willThrowException(new \Exception("Database error"));

        $this->expectException(\Exception::class);
        $this->userUseCases->activateAccount('valid-token');
    }

    public function test_login_general_exception(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willThrowException(new \Exception("Database error"));

        $this->expectException(\Exception::class);
        $this->userUseCases->login(['email' => 'test@example.com', 'password' => 'password']);
    }

    public function test_login_audit_trail(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];
        $user = new User($credentials);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($credentials['email'])
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('updateLastLogin')
            ->with($user);

        auth('api')->shouldReceive('attempt')
            ->once()
            ->with([$credentials['email'], $credentials['password']])
            ->andReturn(true);

        $result = $this->userUseCases->login($credentials);
        $this->assertIsArray($result);
    }

    public function test_logout_success(): void
    {
        $user = new User(['id' => 1]);

        $this->userRepository->expects($this->once())
            ->method('logout')
            ->with($user)
            ->willReturn(true);

        $result = $this->userUseCases->logout($user);
        $this->assertTrue($result);
    }

    public function test_logout_fails(): void
    {
        $user = new User(['id' => 1]);

        $this->userRepository->expects($this->once())
            ->method('logout')
            ->with($user)
            ->willThrowException(new \Exception("Logout failed"));

        $this->expectException(\Exception::class);
        $this->userUseCases->logout($user);
    }

    public function test_activate_account_not_found_exception_in_try(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token no encontrado");
        $this->userUseCases->activateAccount('invalid-token');
    }

    public function test_activate_account_unprocessable_exception_in_try(): void
    {
        $token = 'valid-token';
        $user = new User(['id' => 1]);

        $this->userRepository->expects($this->exactly(2))
            ->method('findByToken')
            ->with($token)
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('activate')
            ->with($user)
            ->willThrowException(new \Exception("Unprocessable"));

        $this->expectException(\Exception::class);
        $this->userUseCases->activateAccount($token);
    }

    public function test_activate_account_auth_exception_in_try(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByToken')
            ->willThrowException(new \Exception("Auth error"));

        $this->expectException(\Exception::class);
        $this->userUseCases->activateAccount('valid-token');
    }

    public function test_login_exception_with_user_found(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];
        $user = new User($credentials);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($credentials['email'])
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('updateLastLogin')
            ->with($user)
            ->willThrowException(new \Exception("Login error"));

        auth('api')->shouldReceive('attempt')
            ->once()
            ->with([$credentials['email'], $credentials['password']])
            ->andReturn(true);

        $this->expectException(\Exception::class);
        $this->userUseCases->login($credentials);
    }
}