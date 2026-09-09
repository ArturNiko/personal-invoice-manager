<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_exchange_rates_endpoint_returns_rates_and_caches_them(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'base_code' => 'EUR',
                'rates' => [
                    'USD' => 1.1,
                    'GBP' => 0.85,
                    'JPY' => 170,
                    'AMD' => 0,
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/exchange-rates')
            ->assertOk()
            ->assertJsonPath('base', 'EUR')
            ->assertJsonPath('rates.USD', 1.1)
            ->assertJsonPath('rates.GBP', 0.85);

        // Currencies the provider does not cover (or reports as zero) fall
        // back to the bundled table.
        Http::assertSentCount(1);

        // A second request serves the cached copy without a new upstream call.
        $this->actingAs($user)
            ->getJson('/exchange-rates')
            ->assertOk()
            ->assertJsonPath('rates.USD', 1.1);

        Http::assertSentCount(1);
    }

    public function test_exchange_rates_endpoint_falls_back_when_provider_is_down(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response(status: 500),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/exchange-rates')
            ->assertOk()
            ->assertJsonPath('base', 'EUR')
            ->assertJsonPath('rates.USD', 1.08)
            ->assertJsonPath('rates.AMD', 430);
    }

    public function test_profile_currency_can_be_updated(): void
    {
        $user = User::factory()->create(['currency' => 'EUR']);

        $this->actingAs($user)
            ->putJson('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'currency' => 'USD',
            ])
            ->assertOk()
            ->assertJsonPath('user.currency', 'USD');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'currency' => 'USD',
        ]);
    }
}
