<?php

namespace Tests\Unit\Notifications\Auth;

use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\Notifications\Auth\UserRegisteredNotificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class UserRegisteredNotificacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_returns_mail_message_with_correct_data()
    {
        // Ensure roles and status exist for factory 
        // (Factories often create them or assume seeders. Use RefreshDatabase to be clean)
        // Assume UserFactory handles this or we need to seed.
        // Let's create prerequisites if factory doesn't.
        if (Role::count() == 0) {
             Role::create(['name' => 'user', 'uuid' => 'role-uuid']);
             Role::create(['name' => 'admin', 'uuid' => 'admin-uuid']);
        }
        if (Status::count() == 0) {
             Status::create(['name' => 'pending', 'uuid' => 'pending-uuid']);
             Status::create(['name' => 'active', 'uuid' => 'active-uuid']);
        }

        $user = User::factory()->create();
        
        // Refresh to load activationToken relation
        $user->refresh();
        
        $notification = new UserRegisteredNotificacion();
        
        $mailMessage = $notification->toMail($user);
        
        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Bienvenido a ' . config('app.name'), $mailMessage->subject);
        $this->assertEquals('emails.new-user', $mailMessage->view);
        $this->assertEquals($user->uuid, $mailMessage->viewData['user']->uuid);
        
        $expectedUrl = config('app.url_testing') . '/api/auth/activate/' . $user->activationToken->token;
        $this->assertEquals($expectedUrl, $mailMessage->viewData['activationUrl']);
    }

    public function test_via_returns_mail_channel()
    {
        $notification = new UserRegisteredNotificacion();
        $user = new User(); // Dummy user
        $channels = $notification->via($user);
        $this->assertEquals(['mail'], $channels);
    }
    
    public function test_to_array_returns_empty_array()
    {
        $notification = new UserRegisteredNotificacion();
        $user = new User(); // Dummy user
        $data = $notification->toArray($user);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
}
