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
    }
}
