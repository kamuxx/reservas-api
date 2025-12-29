<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class ValidateAccountTest extends TestCase
{
    /**
     * is validate route
     */
    public function test_validate_route()
    {
        $response = $this->get("api/auth/activate");
        $this->assertNotEquals(404, $response->status());
    }


    public function test_validate_account()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register', $formData);

        $response->assertStatus(201);
        $content = $response->json();
        $this->assertArrayHasKey('data', $content);
        $user = $content['data'];
        $this->assertArrayHasKey('role', $user);
        $this->assertArrayHasKey('status', $user);
        $this->assertArrayHasKey('activation_token', $user);
        ["role" => $role] =  $user;
        ["status" => $status] =  $user;
        ["activation_token" => $activation_token] =  $user;
        $this->assertEquals('user', $role["name"]);
        $this->assertEquals('pending', $status["name"]);

        $this->assertNUll($activation_token["used_at"]);
        $this->assertNUll($activation_token["validated_at"]);

        $now = Carbon::now();
        $this->assertTrue($now->lte($activation_token["expired_at"]));

        $response = $this->postJson("api/auth/activate", [
            "token" => $activation_token["token"],
            "activation_code" => $activation_token["activation_code"],
        ]);
        $response->assertStatus(200);
        $content = $response->json();
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals('success', $content['status']);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals('Cuenta activada exitosamente', $content['message']);
    }

    public function test_validate_account_with_token_used()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register', $formData);

        $response->assertStatus(201);
        $content = $response->json();
        $this->assertArrayHasKey('data', $content);
        $user = $content['data'];
        $this->assertArrayHasKey('role', $user);
        $this->assertArrayHasKey('status', $user);
        $this->assertArrayHasKey('activation_token', $user);
        ["role" => $role] =  $user;
        ["status" => $status] =  $user;
        ["activation_token" => $activation_token] =  $user;
        $this->assertEquals('user', $role["name"]);
        $this->assertEquals('pending', $status["name"]);

        $this->assertNUll($activation_token["used_at"]);
        $this->assertNUll($activation_token["validated_at"]);

        $now = Carbon::now();
        $this->assertTrue($now->lte($activation_token["expired_at"]));

        $response = $this->postJson("api/auth/activate", [
            "token" => $activation_token["token"],
            "activation_code" => $activation_token["activation_code"],
        ]);
        $response->assertStatus(200);
        $content = $response->json();
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals('success', $content['status']);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals('Cuenta activada exitosamente', $content['message']);

        $response = $this->postJson("api/auth/activate", [
            "token" => $activation_token["token"],
            "activation_code" => $activation_token["activation_code"],
        ]);

        $response->assertStatus(422);

        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('El token ya ha sido utilizado', $content['message']);
    }

    public function test_validate_account_with_invalid_token()
    {
        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ];

        $response = $this->postJson("api/auth/activate", [
            "token" => "invalid_token",
            "activation_code" => 123456,
        ], $headers);

        $this->assertEquals(404, $response->status(), "Error interno al validar la cuenta");
        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('No se encontro el token', $content['message']);
    }

    public function test_validate_account_without_token()
    {
        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ];
        $response = $this->postJson("api/auth/activate", [
            "activation_code" => 123456,
        ], $headers);

        $this->assertEquals(422, $response->status(), "Error interno al validar la cuenta");

        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('El token es obligatorio', $content['message']);
    }

    public function test_validate_account_with_string_activation_code()
    {
        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ];

        $response = $this->postJson("api/auth/activate", [
            "token" => "invalid_token",
            "activation_code" => "token",
        ], $headers);

        $this->assertEquals(422, $response->status(), "Error interno al validar la cuenta");
        $content = $response->json();

        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('El codigo de activacion debe tener 6 digitos', $content['message']);
    }

    public function test_validate_account_with_invalid_format_activation_code()
    {
        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ];

        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register', $formData);

        $response->assertStatus(201);
        $contentUser = $response->json();

        $response = $this->postJson("api/auth/activate", [
            "token" => $contentUser['data']['activation_token']['token'],
            "activation_code" => "12345",
        ], $headers);


        $this->assertEquals(422, $response->status(), "Error interno al validar la cuenta");
        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('El codigo de activacion debe tener 6 digitos', $content['message']);
    }

    public function test_validate_account_with_invalid_activation_code()
    {
        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ];

        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register', $formData);

        $response->assertStatus(201);
        $contentUser = $response->json();

        $response = $this->postJson("api/auth/activate", [
            "token" => $contentUser['data']['activation_token']['token'],
            "activation_code" => 111111,
        ], $headers);


        $this->assertEquals(422, $response->status(), "Error interno al validar la cuenta");
        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('El codigo de activacion no es valido', $content['message']);
    }

    public function test_validate_account_with_expired_token()
    {
        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ];

        // 1. Crear usuario
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];
        $response = $this->postJson('api/auth/register', $formData);
        $contentUser = $response->json();
        $token = $contentUser['data']['activation_token']['token'];

        // 2. Expirar el token manualmente (Hack de BD para el test)
        // Usamos el Repositorio o Modelo directo
        \App\Models\UserActivationToken::where('token', $token)->update([
            'expired_at' => \Carbon\Carbon::now()->subDay() // Un día atrás
        ]);

        // 3. Intentar activar
        $response = $this->postJson("api/auth/activate", [
            "token" => $token,
            "activation_code" => $contentUser['data']['activation_token']['activation_code'],
        ], $headers);

        // 4. Aserciones
        $this->assertEquals(422, $response->status(), "Debería fallar por expiración");
        $content = $response->json();
        $this->assertStringContainsString('El token ha expirado', $content['message']);
    }
}
