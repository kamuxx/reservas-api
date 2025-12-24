<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
     use RefreshDatabase;

    public function test_user_auto_generates_uuid_on_creation(): void
    {
         // 1. Crear un usuario simple (sin pasar UUID)
        $user = User::create([
            'name' => 'Unit Test User',
            'email' => 'unit@test.com',
            'password' => 'password123',
            'phone' => '123456'
        ]);
        // 2. Verificar que el UUID se generó
        $this->assertNotNull($user->uuid);
        $this->assertIsString($user->uuid);
        $this->assertEquals(36, strlen($user->uuid));

        $rol = Role::where('name', 'user')->first();
        $this->assertNotNull($rol);
        $user->load(['role','status']);
        $this->assertEquals('user', $user->role->name);
        $this->assertEquals('pending', $user->status->name);
        $this->assertInstanceof(Role::class, $user->role);
        $this->assertInstanceof(Status::class, $user->status);
    }
}
