<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class ValidateAccountTest extends TestCase
{
    /**
     * is validate route
     */
   public function test_validate_route(){
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

        $response = $this->post('api/auth/register',$formData);

        $response->assertStatus(201);
        $content = $response->json();
        $this->assertArrayHasKey('data',$content);
        $user = $content['data'];
        $this->assertArrayHasKey('role',$user);
        $this->assertArrayHasKey('status',$user);
        $this->assertArrayHasKey('activation_token',$user);
        ["role" => $role] =  $user;
        ["status" => $status] =  $user;
        ["activation_token" => $activation_token] =  $user;
        $this->assertEquals('user',$role["name"]);
        $this->assertEquals('pending',$status["name"]);

        $this->assertNUll($activation_token["used_at"]);
        $this->assertNUll($activation_token["validated_at"]);

        $now = Carbon::now();
        $this->assertTrue($now->lte($activation_token["expires_at"]));

        $response = $this->post("api/auth/activate", [
            "token" => $activation_token["token"],
            "activation_code" => $activation_token["activation_code"],
        ]);
        $response->assertStatus(200);
        $content = $response->json();
        $this->assertArrayHasKey('status',$content);
        $this->assertEquals('success',$content['status']);
        $this->assertArrayHasKey('message',$content);
        $this->assertEquals('Cuenta activada exitosamente',$content['message']);
   }

   public function test_validate_account_with_invalid_token(){
       $headers = [
           "Accept" => "application/json",
           "Content-Type" => "application/json",
       ];
       $response = $this->post("api/auth/activate", [            
            "activation_code" => 123456,
        ], $headers);

        $response->assertStatus(422);
   } 

}
