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

class InvoiceOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_invoice_can_have_occurrence_rows(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'title' => 'Cloud storage',
            'status' => 'pending',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => null,
            'price' => 39.99,
            'currency' => InvoiceCurrency::EUR->value,
            'type' => InvoiceType::RECURRING->value,
            'recurrence' => InvoiceReccuranceType::MONTHLY->value,
        ]);

        $this->assertCount(6, $invoice->fresh()->occurrences);
        $this->assertDatabaseHas('invoice_occurrences', [
            'invoice_id' => $invoice->id,
            'status' => InvoiceOccurrenceStatus::PENDING->value,
        ]);

        $invoice->occurrences()->first()->update([
            'status' => InvoiceOccurrenceStatus::PAID->value,
            'paid_at' => now(),
        ]);

        $this->assertDatabaseHas('invoice_occurrences', [
            'invoice_id' => $invoice->id,
            'status' => InvoiceOccurrenceStatus::PAID->value,
        ]);
    }

    public function test_recurring_invoice_generates_occurrences_on_create(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'title' => 'Gym membership',
            'status' => 'pending',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => null,
            'price' => 49.99,
            'currency' => InvoiceCurrency::EUR->value,
            'type' => InvoiceType::RECURRING->value,
            'recurrence' => InvoiceReccuranceType::MONTHLY->value,
        ]);

        $this->assertNotNull($invoice->fresh()->occurrences()->first());
        $this->assertCount(6, $invoice->fresh()->occurrences);
    }
}
