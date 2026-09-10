<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_root_route(): void
    {
        $this->get('/')
            ->assertRedirect('/login');
    }

    public function test_unverified_user_is_redirected_to_verification_page_when_accessing_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/verify-email');
    }

    public function test_successful_login_returns_dashboard_redirect_for_verified_users(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->json('POST', '/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('redirect', '/dashboard');
    }

    public function test_unverified_user_returns_verification_redirect_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $this->json('POST', '/login', [
            'email' => 'pending@example.com',
            'password' => 'password123',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('redirect', '/verify-email');
    }

    public function test_registered_user_returns_verification_redirect(): void
    {
        $this->json('POST', '/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('redirect', '/verify-email');
    }

    public function test_failed_login_returns_validation_errors(): void
    {
        $this->json('POST', '/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_request_returns_json_message_when_requested_as_json(): void
    {
        User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $this->json('POST', '/forgot-password', [
            'email' => 'reset@example.com',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['message']);
    }

    public function test_verification_links_expire_after_fifteen_minutes(): void
    {
        $this->assertSame(15, config('auth.verification.expire'));
    }
}
