<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Testing\Fakes\Fake;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class RegisterNewUserTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    
        public function test_valid_route(): void
        {
            $response = $this->post('api/auth/register');

            // Solo validamos que la ruta existe y responde (no es 404)
            $this->assertNotEquals(404, $response->status());
        }

    public function test_register_new_user_with_valid_data()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->post('api/auth/register',$formData);

        $response->assertStatus(201);
        $content = $response->json();
        $this->assertArrayHasKey('data',$content);
        $user = $content['data'];
        $this->assertArrayHasKey('role',$user);
        $this->assertArrayHasKey('status',$user);
        ["role" => $role] =  $user;
        ["status" => $status] =  $user;
        $this->assertEquals('user',$role["name"]);
        $this->assertEquals('pending',$status["name"]);
    }

    public function test_register_new_user_with_email_duplicate()
    {
        $password = "User@2025";
        $email = $this->faker->email();
        $formData = [
            "name" => $this->faker->name(),
            "email" => $email,
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(201);

        $formData = [
            "name" => $this->faker->name(),
            "email" => $email,
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422);
    }

    public function test_register_new_user_without_name()
    {
        $password = "User@2025";
        $formData = [
            "name" => "",
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
    }

    public function test_register_new_user_without_email()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    }

    public function test_register_new_user_without_password()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
    }

    public function test_register_new_user_without_password_confirmation()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
    }

    public function test_register_new_user_without_phone()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => $password,
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
    }

    public function test_register_new_user_with_invalid_password()
    {
        $password = "123";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => "123",
            "password_confirmation" => "123",
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
    }

    public function test_register_new_user_with_invalid_email_format()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => "user@",
            "password" => $password,
            "password_confirmation" => $password,
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    }

    public function test_register_new_user_with_password_confirmation_mismatch()
    {
        $password = "User@2025";
        $formData = [
            "name" => $this->faker->name(),
            "email" => $this->faker->email(),
            "password" => $password,
            "password_confirmation" => "123",
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson('api/auth/register',$formData);
        $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
    }
    
}
