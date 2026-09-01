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

    public function test_successful_login_redirects_to_calendar(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ])->assertRedirect('/calendar');
    }

    public function test_failed_login_redirects_back_to_login_page(): void
    {
        $this->from('/login')
            ->post('/login', [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }
}
