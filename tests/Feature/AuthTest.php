<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_log_in_with_the_test_credentials(): void
    {
        $this->seed();

        $response = $this->post(route('login.post'), [
            'email' => 'admin@agilevet.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        $this->seed();

        $response = $this->post(route('login.post'), [
            'email' => 'admin@agilevet.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
