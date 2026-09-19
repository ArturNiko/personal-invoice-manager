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

    public function test_recurring_invoice_generates_occurrences_through_end_date(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'title' => 'Cloud storage',
            'status' => 'pending',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addMonths(3)->toDateString(),
            'price' => 39.99,
            'currency' => InvoiceCurrency::EUR->value,
            'type' => InvoiceType::RECURRING->value,
            'recurrence' => InvoiceReccuranceType::MONTHLY->value,
        ]);

        $occurrences = $invoice->fresh()->occurrences()->orderBy('due_date')->get();

        $this->assertCount(4, $occurrences);
        $this->assertDatabaseHas('invoice_occurrences', [
            'invoice_id' => $invoice->id,
            'status' => InvoiceOccurrenceStatus::PENDING->value,
        ]);
        $this->assertSame(
            now()->startOfMonth()->addMonths(3)->toDateString(),
            $occurrences->last()->due_date->toDateString()
        );
    }

    public function test_recurring_invoice_rolls_forward_without_end_date(): void
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

        $this->assertCount(7, $invoice->fresh()->occurrences);

        $this->travelTo(now()->addMonths(7));

        $invoice->fresh()->syncOccurrences();

        $refreshedInvoice = $invoice->fresh();

        $this->assertCount(6, $refreshedInvoice->occurrences()->whereDate('due_date', '>=', now()->toDateString())->get());
        $this->assertSame(
            InvoiceOccurrenceStatus::OVERDUE->value,
            $refreshedInvoice->occurrences()->orderBy('due_date')->first()->status
        );
    }

    public function test_recurrence_is_ignored_on_update_for_any_invoice(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'title' => 'Vodafone',
            'status' => 'pending',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => null,
            'price' => 29.99,
            'currency' => InvoiceCurrency::EUR->value,
            'type' => InvoiceType::ONE_TIME->value,
            'recurrence' => null,
        ]);

        $response = $this->actingAs($user)->putJson('/invoices/'.$invoice->id, [
            'title' => 'Vodafone',
            'type' => InvoiceType::ONE_TIME->value,
            'start_date' => $invoice->start_date->toDateString(),
            'currency' => InvoiceCurrency::EUR->value,
            'recurrence' => InvoiceReccuranceType::YEARLY->value,
            'price' => 29.99,
            'status' => 'pending',
        ]);

        $response->assertOk();
        $this->assertNull($invoice->fresh()->recurrence);
    }
}
