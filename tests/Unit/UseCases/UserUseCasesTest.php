<?php

namespace Tests\Unit\UseCases;

use Tests\TestCase;
use UseCases\UserUseCases;
use Repositories\UserRepository;
use Repositories\TokenRepository;
use App\Models\User;
use App\Models\UserActivationToken;
use App\Notifications\Auth\UserRegisteredNotificacion;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UserUseCasesTest extends TestCase
{
    use RefreshDatabase;

    private $userRepository;
    private $tokenRepository;
    private $userUseCases;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->tokenRepository = Mockery::mock(TokenRepository::class);
        $this->userUseCases = new UserUseCases($this->userRepository, $this->tokenRepository);
        
        // Ensure roles and statuses exist for factories
        if (!\App\Models\Role::where('name', 'user')->exists()) {
            \App\Models\Role::create(['name' => 'user', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }
        if (!\App\Models\Status::where('name', 'active')->exists()) {
            \App\Models\Status::create(['name' => 'active', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_new_user_success(): void
    {
        Notification::fake();
        
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ];

        $user = User::factory()->make(['email' => $data['email']]);
        
        $this->userRepository->shouldReceive('createNewUser')
            ->once()
            ->with($data)
            ->andReturn($user);

        $result = $this->userUseCases->registerNewUser($data);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($data['email'], $result->email);
        Notification::assertSentTo($user, UserRegisteredNotificacion::class);
    }

    public function test_register_new_user_throws_exception_on_failure(): void
    {
        $data = ['email' => 'fail@example.com'];

        $this->userRepository->shouldReceive('createNewUser')
            ->once()
            ->andThrow(new \Exception("Error al insertar el usuario"));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error al insertar el usuario");

        $this->userUseCases->registerNewUser($data);
    }

    public function test_activate_account_success(): void
    {
        $token = 'valid-token';
        $code = 123456;
        
        $role = \App\Models\Role::create(['name' => 'user', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        $status = \App\Models\Status::create(['name' => 'pending', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        
        $user = User::factory()->create([
            'role_id' => $role->uuid,
            'status_id' => $status->uuid
        ]);

        $oToken = Mockery::mock(UserActivationToken::class)->makePartial();
        $oToken->uuid = 'token-uuid';
        // Use a mock for the user inside the token if we need to call shouldReceive on it
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->uuid = $user->uuid;
        $mockUser->shouldReceive('isActive')->andReturn(false);
        $oToken->user = $mockUser;
        
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(false);
        $oToken->shouldReceive('isValidated')->andReturn(false);
        $oToken->shouldReceive('isValidCode')->with($code)->andReturn(true);

        $this->tokenRepository->shouldReceive('getByToken')
            ->once()
            ->with($token)
            ->andReturn($oToken);

        $this->tokenRepository->shouldReceive('update')
            ->once()
            ->with(UserActivationToken::class, ["uuid" => "token-uuid"], Mockery::any());

        $this->userRepository->shouldReceive('activateUser')
            ->once()
            ->with(["uuid" => "token-uuid"]);

        $this->userUseCases->activateAccount($token, $code);
        $this->assertTrue(true); // If no exception, it's success
    }

    public function test_activate_account_token_not_found(): void
    {
        $this->tokenRepository->shouldReceive('getByToken')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->userUseCases->activateAccount('invalid', 123);
    }

    public function test_activate_account_token_expired(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class);
        $oToken->shouldReceive('isExpired')->andReturn(true);

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage("El token ha expirado");
        $this->userUseCases->activateAccount('expired', 123);
    }

    public function test_login_success(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'Tost@123'];
        
        $user = Mockery::mock(User::class)->makePartial();
        $user->uuid = 'user-uuid';
        $user->shouldReceive('isActive')->andReturn(true);
        $user->shouldReceive('isValidPassword')->with('Tost@123')->andReturn(true);
        $user->shouldReceive('load')->andReturnSelf();

        $this->userRepository->shouldReceive('getOneBy')
            ->once()
            ->andReturn($user);

        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('attempt')->with($credentials)->andReturn('fake-jwt-token');

        $this->userRepository->shouldReceive('updateLastLoginAt')->once()->with($user);

        $result = $this->userUseCases->login($credentials);
        
        $this->assertEquals('fake-jwt-token', $result);
    }

    public function test_login_user_not_found(): void
    {
        $credentials = ['email' => 'none@example.com', 'password' => 'pass'];
        
        $this->userRepository->shouldReceive('getOneBy')->andReturn(null);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage("Las credenciales son incorrectas");

        $this->userUseCases->login($credentials);
    }

    public function test_login_user_inactive(): void
    {
        $credentials = ['email' => 'inactive@example.com', 'password' => 'pass'];
        $user = Mockery::mock(User::class)->makePartial();
        $user->uuid = 'user-uuid';
        $user->shouldReceive('isActive')->andReturn(false);
        $user->shouldReceive('load')->andReturnSelf();

        $this->userRepository->shouldReceive('getOneBy')->andReturn($user);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->userUseCases->login($credentials);
    }

    public function test_logout_success(): void
    {
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('logout')->once();

        $result = $this->userUseCases->logout();
        $this->assertTrue($result);
    }
}
