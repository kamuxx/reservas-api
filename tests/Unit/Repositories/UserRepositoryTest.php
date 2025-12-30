<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Repositories\UserRepository;
use App\Models\User;
use App\Models\Status;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private $role;
    private $statusActive;
    private $statusPending;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->role = Role::where('name', 'user')->first() ?: Role::create(['name' => 'user', 'uuid' => (string) Str::uuid()]);
        $this->statusActive = Status::where('name', 'active')->first() ?: Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);
        $this->statusPending = Status::where('name', 'pending')->first() ?: Status::create(['name' => 'pending', 'uuid' => (string) Str::uuid()]);
    }

    public function test_create_new_user(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'phone' => '123456789',
            'role_id' => $this->role->uuid,
            'status_id' => $this->statusPending->uuid,
        ];

        $result = UserRepository::createNewUser($data);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_update_user(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->role->uuid,
            'status_id' => $this->statusPending->uuid,
        ]);

        $result = UserRepository::updateUser(['uuid' => $user->uuid], ['name' => 'Updated Name']);
        $this->assertTrue($result);
        $this->assertDatabaseHas('users', ['uuid' => $user->uuid, 'name' => 'Updated Name']);
    }

    public function test_activate_user(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->role->uuid,
            'status_id' => $this->statusPending->uuid,
        ]);

        $result = UserRepository::activateUser(['uuid' => $user->uuid]);
        $this->assertTrue($result);
        $this->assertDatabaseHas('users', [
            'uuid' => $user->uuid, 
            'status_id' => $this->statusActive->uuid
        ]);
    }

    public function test_activate_user_status_missing(): void
    {
        Status::where('name', 'active')->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error al activar el usuario");

        UserRepository::activateUser(['uuid' => 'some-uuid']);
    }

    public function test_update_last_login_at(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->role->uuid,
            'status_id' => $this->statusActive->uuid,
        ]);

        $result = UserRepository::updateLastLoginAt($user);
        $this->assertTrue($result);
        
        $updatedUser = User::where('uuid', $user->uuid)->first();
        $this->assertNotNull($updatedUser->last_login_at);
    }
}
