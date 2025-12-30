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

    public function test_activate_account_token_used(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class);
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(true);

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage("El token ya ha sido utilizado");
        $this->userUseCases->activateAccount('used', 123);
    }

    public function test_activate_account_invalid_code(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class);
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(false);
        $oToken->shouldReceive('isValidated')->andReturn(false);
        $oToken->shouldReceive('isValidCode')->andReturn(false);

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage("El codigo de activacion no es valido");
        $this->userUseCases->activateAccount('valid-token', 123);
    }

    public function test_activate_account_user_already_active(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class)->makePartial();
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(false);
        $oToken->shouldReceive('isValidated')->andReturn(false);
        $oToken->shouldReceive('isValidCode')->andReturn(true);
        
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('isActive')->andReturn(true);
        $oToken->setRelation('user', $mockUser);

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage("El usuario ya esta activo");
        $this->userUseCases->activateAccount('valid-token', 123);
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

    public function test_login_invalid_password(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'wrong'];
        $user = Mockery::mock(User::class)->makePartial();
        $user->uuid = 'user-uuid';
        $user->shouldReceive('load')->andReturnSelf();
        $user->shouldReceive('isActive')->andReturn(true);
        $user->shouldReceive('isValidPassword')->andReturn(false);

        $this->userRepository->shouldReceive('getOneBy')->andReturn($user);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->userUseCases->login($credentials);
    }

    public function test_login_attempt_fails(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'pass'];
        $user = Mockery::mock(User::class)->makePartial();
        $user->uuid = 'user-uuid';
        $user->shouldReceive('load')->andReturnSelf();
        $user->shouldReceive('isActive')->andReturn(true);
        $user->shouldReceive('isValidPassword')->andReturn(true);

        $this->userRepository->shouldReceive('getOneBy')->andReturn($user);
        
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('attempt')->andReturn(false);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->userUseCases->login($credentials);
    }

    public function test_register_new_user_notify_fails(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ];

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('load')->andReturnSelf();
        $user->shouldReceive('notify')->andThrow(new \Exception("SMTP Error"));

        $this->userRepository->shouldReceive('createNewUser')->once()->andReturn($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error al enviar notificación al usuario:    SMTP Error");

        $this->userUseCases->registerNewUser($data);
    }

    public function test_register_new_user_invalid_return(): void
    {
        $data = ['email' => 'test@example.com'];

        $this->userRepository->shouldReceive('createNewUser')->once()->andThrow(new \TypeError("Return value must be of type App\Models\User, null returned"));

        $this->expectException(\TypeError::class);
        $this->userUseCases->registerNewUser($data);
    }

    public function test_activate_account_general_exception(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class)->makePartial();
        $oToken->uuid = 'token-uuid';
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(false);
        $oToken->shouldReceive('isValidated')->andReturn(false);
        $oToken->shouldReceive('isValidCode')->andReturn(true);
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('isActive')->andReturn(false);
        $oToken->user = $mockUser;

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);
        $this->tokenRepository->shouldReceive('update')->andThrow(new \Exception("DB Error"));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error al activar la cuenta: DB Error");

        $this->userUseCases->activateAccount('token', 123);
    }

    public function test_login_general_exception(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'pass'];
        $this->userRepository->shouldReceive('getOneBy')->andThrow(new \Exception("DB Connection failed"));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("DB Connection failed");

        $this->userUseCases->login($credentials);
    }

    public function test_login_audit_trail(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'Tost@123'];
        $user = Mockery::mock(User::class)->makePartial();
        $user->uuid = 'user-uuid';
        $user->shouldReceive('isActive')->andReturn(true);
        $user->shouldReceive('isValidPassword')->andReturn(true);
        $user->shouldReceive('load')->andReturnSelf();

        $this->userRepository->shouldReceive('getOneBy')->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLoginAt')->andReturn(true);
        
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('attempt')->andReturn('token');

        $this->userUseCases->login($credentials, '127.0.0.1', 'Mozilla/5.0');

        $this->assertDatabaseHas('login_audit_trails', [
            'user_uuid' => 'user-uuid',
            'email_attempt' => 'test@example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'status' => 'success'
        ]);
    }

    public function test_logout_success(): void
    {
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('logout')->once();

        $result = $this->userUseCases->logout();
        $this->assertTrue($result);
    }

    public function test_logout_fails(): void
    {
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('logout')->andThrow(new \Exception("Logout failed"));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error al cerrar sesión: Logout failed");

        $this->userUseCases->logout();
    }

    public function test_activate_account_not_found_exception_in_try(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class)->makePartial();
        $oToken->uuid = 'token-uuid';
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(false);
        $oToken->shouldReceive('isValidated')->andReturn(false);
        $oToken->shouldReceive('isValidCode')->andReturn(true);
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('isActive')->andReturn(false);
        $oToken->user = $mockUser;

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);
        $this->tokenRepository->shouldReceive('update')->andThrow(new NotFoundHttpException("Not Found"));

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("Error al activar la cuenta: Not Found");

        $this->userUseCases->activateAccount('token', 123);
    }

    public function test_activate_account_unprocessable_exception_in_try(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class)->makePartial();
        $oToken->uuid = 'token-uuid';
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(false);
        $oToken->shouldReceive('isValidated')->andReturn(false);
        $oToken->shouldReceive('isValidCode')->andReturn(true);
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('isActive')->andReturn(false);
        $oToken->user = $mockUser;

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);
        $this->tokenRepository->shouldReceive('update')->andThrow(new UnprocessableEntityHttpException("Unprocessable"));

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage("Error al activar la cuenta: Unprocessable");

        $this->userUseCases->activateAccount('token', 123);
    }

    public function test_activate_account_auth_exception_in_try(): void
    {
        $oToken = Mockery::mock(UserActivationToken::class)->makePartial();
        $oToken->uuid = 'token-uuid';
        $oToken->shouldReceive('isExpired')->andReturn(false);
        $oToken->shouldReceive('isUsed')->andReturn(false);
        $oToken->shouldReceive('isValidated')->andReturn(false);
        $oToken->shouldReceive('isValidCode')->andReturn(true);
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('isActive')->andReturn(false);
        $oToken->user = $mockUser;

        $this->tokenRepository->shouldReceive('getByToken')->andReturn($oToken);
        $this->tokenRepository->shouldReceive('update')->andThrow(new \Illuminate\Auth\AuthenticationException("Auth Failed"));

        $this->expectException(\Illuminate\Auth\AuthenticationException::class);
        $this->expectExceptionMessage("Error al activar la cuenta: Auth Failed");

        $this->userUseCases->activateAccount('token', 123);
    }

    public function test_login_exception_with_user_found(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'pass'];
        $user = Mockery::mock(User::class)->makePartial();
        $user->uuid = 'user-uuid';
        $user->shouldReceive('load')->andReturnSelf();
        $user->shouldReceive('isActive')->andReturn(true);
        $user->shouldReceive('isValidPassword')->andReturn(true);

        $this->userRepository->shouldReceive('getOneBy')->andReturn($user);
        
        Auth::shouldReceive('guard')->with('api')->andReturnSelf();
        Auth::shouldReceive('attempt')->andThrow(new \Exception("Unexpected Error"));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unexpected Error");

        $this->userUseCases->login($credentials, '127.0.0.1', 'Mozilla');
        
        $this->assertDatabaseHas('login_audit_trails', [
            'user_uuid' => 'user-uuid',
            'status' => 'failed',
            'failure_reason' => 'Unexpected Error'
        ]);
    }
}
