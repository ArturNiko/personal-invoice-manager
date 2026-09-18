<?php

namespace Tests\Feature;

use App\Enums\InvoiceCurrency;
use App\Enums\InvoiceOccurrenceStatus;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceOccurrenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_and_update_invoice_occurrences(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'title' => 'Streaming service',
            'status' => 'active',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => null,
            'price' => 19.99,
            'currency' => InvoiceCurrency::EUR->value,
            'type' => InvoiceType::RECURRING->value,
            'recurrence' => InvoiceReccuranceType::MONTHLY->value,
        ]);

        $occurrence = $invoice->occurrences()->create([
            'due_date' => now()->startOfMonth()->addMonth()->toDateString(),
            'amount' => 19.99,
            'currency' => InvoiceCurrency::EUR->value,
            'status' => InvoiceOccurrenceStatus::PENDING->value,
        ]);

        $this->actingAs($user)
            ->getJson('/invoices/'.$invoice->id.'/occurrences')
            ->assertOk()
            ->assertJsonPath('0.id', $occurrence->id)
            ->assertJsonPath('0.status', InvoiceOccurrenceStatus::PENDING->value);

        $this->actingAs($user)
            ->putJson('/invoices/'.$invoice->id.'/occurrences/'.$occurrence->id, [
                'status' => InvoiceOccurrenceStatus::PAID->value,
            ])
            ->assertOk()
            ->assertJsonPath('status', InvoiceOccurrenceStatus::PAID->value)
            ->assertJsonPath('paid_at', fn ($value) => ! empty($value));
    }
}
