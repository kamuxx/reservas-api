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
        //$this->withoutExceptionHandling([NotFoundHttpException::class]);
        $response = $this->post('api/auth/register');

        $response->assertStatus(201);
    }

    public function test_register_new_user_with_valid_data(){
        $formData = [
            "nombre" => $this->faker->name(),
            "email" => $this->faker->email(),
            "phone" => $this->faker->phoneNumber(),
        ];

        $response = $this->post('api/auth/register',$formData);

        $response->assertStatus(201);
    }
}
