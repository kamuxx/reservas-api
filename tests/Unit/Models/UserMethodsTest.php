<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMethodsTest extends TestCase
{
    // TestCase already uses RefreshDatabase and seeds roles/status

    public function test_assign_initial_role_throws_exception_if_role_missing()
    {
        Role::query()->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Role not found');

        $user = new User();
        $user->assingInitialRoleAndStatus();
    }

    public function test_assign_initial_status_throws_exception_if_status_missing()
    {
        Status::query()->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Status not found');

        $user = new User();
        $user->assingInitialRoleAndStatus();
    }

    public function test_assign_token_verification_does_not_create_duplicate_token()
    {
        // Seeds are already run by TestCase::setUp

        $user = User::factory()->create();
        $this->assertNotNull($user->activationToken);

        $initialToken = $user->activationToken->token;

        // Call again
        $user->assignTokenVerification();
        $user->refresh();

        $this->assertEquals($initialToken, $user->activationToken->token);
    }

    public function test_is_active_returns_false_if_status_incorrect()
    {
        $pending = Status::where('name', 'pending')->first();
        if (!$pending) {
            $pending = Status::create(['name' => 'pending', 'uuid' => 'pending-uuid']);
        }

        $user = User::factory()->create(['status_id' => $pending->uuid]);
        $this->assertFalse($user->isActive());
    }

    public function test_is_admin_returns_false_if_role_incorrect()
    {
        $roleUser = Role::where('name', 'user')->first();

        $user = User::factory()->create(['role_id' => $roleUser->uuid]);
        $this->assertFalse($user->isAdmin());
    }
}
