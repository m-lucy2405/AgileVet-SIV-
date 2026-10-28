<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_log_in_with_the_test_credentials(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('users', 3);

        $response = $this->post(route('login.post'), [
            'email' => 'admin@agilevet.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_page_uses_https_urls_behind_a_railway_proxy(): void
    {
        $response = $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('/login');

        $response->assertOk();
        $response->assertSee('href="https://localhost:8000/build/assets/', false);
        $response->assertSee('action="https://localhost:8000/login"', false);
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

    public function test_inactive_user_cannot_log_in_with_valid_credentials(): void
    {
        $this->seed();
        User::query()
            ->where('email', 'admin@agilevet.com')
            ->update(['estado' => false]);

        $response = $this->post(route('login.post'), [
            'email' => 'admin@agilevet.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.post'), [
                'email' => 'test@example.com',
                'password' => 'incorrect-password',
            ]);
        }

        $response = $this->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertTooManyRequests();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $this->seed();
        $this->post(route('login.post'), [
            'email' => 'admin@agilevet.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
