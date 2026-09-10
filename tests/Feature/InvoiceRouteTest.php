<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceRouteTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoices(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Invoice::create([
                'user_id' => $user->id,
                'title' => "Invoice {$i}",
                'start_date' => now()->addDays($i),
                'price' => 100 + $i,
                'currency' => 'EUR',
                'type' => 'one-time',
                'recurrence' => null,
                'status' => 'pending',
            ]);
        }
    }

    public function test_invoices_index_returns_json_for_json_request(): void
    {
        $user = User::factory()->create();
        $this->makeInvoices($user, 3);

        $response = $this->actingAs($user)->getJson('/invoices');

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_invoices_index_serves_spa_shell_for_browser(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/invoices');

        $response->assertOk();
        $this->assertStringContainsString('<div id="app">', $response->getContent());
    }

    public function test_profile_returns_json_for_json_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/profile');

        $response->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_profile_serves_spa_shell_for_browser(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $this->assertStringContainsString('<div id="app">', $response->getContent());
    }
}
