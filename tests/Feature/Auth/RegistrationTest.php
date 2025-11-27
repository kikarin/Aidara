<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->from('/register')->post('/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        // Check if user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        // Check if user is authenticated
        $user = \App\Models\User::where('email', 'test@example.com')->first();
        $this->assertAuthenticatedAs($user);
        
        // Registration sekarang redirect ke registration steps, bukan dashboard
        $response->assertRedirect(route('registration.steps', ['step' => 1], absolute: false));
    }
}
