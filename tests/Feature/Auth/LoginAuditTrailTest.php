<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'cliente'], ['uuid' => (string) Str::uuid()]);
        $status = Status::firstOrCreate(['name' => 'active'], ['uuid' => (string) Str::uuid()]);

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Tost@123'),
            'role_id' => $role->uuid,
            'status_id' => $status->uuid,
        ]);
    }

    public function test_login_success_logs_audit_trail(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Tost@123',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('login_audit_trails', [
            'user_uuid' => $this->user->uuid,
            'email_attempt' => 'test@example.com',
            'status' => 'success',
        ]);
    }

    public function test_login_failed_user_not_found_logs_audit_trail(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'any_password',
        ]);

        $response->assertStatus(422); // Changed from 404 to 422

        $this->assertDatabaseHas('login_audit_trails', [
            'user_uuid' => null,
            'email_attempt' => 'nonexistent@example.com',
            'status' => 'failed',
            'failure_reason' => 'Usuario no encontrado',
        ]);
    }

    public function test_login_failed_wrong_password_logs_audit_trail(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422); // UnprocessableEntityHttpException returns 422

        $this->assertDatabaseHas('login_audit_trails', [
            'user_uuid' => $this->user->uuid,
            'email_attempt' => 'test@example.com',
            'status' => 'failed',
            'failure_reason' => 'Contraseña inválida',
        ]);
    }
}
