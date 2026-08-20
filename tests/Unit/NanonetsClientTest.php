<?php

namespace Tests\Unit;

use App\Services\Nanonets\NanonetsClient;
use PHPUnit\Framework\TestCase;

class NanonetsClientTest extends TestCase
{
    public function test_it_maps_structured_credit_card_statement_payloads(): void
    {
        $client = new NanonetsClient();

        $attributes = $client->buildInvoiceAttributes([
            'title' => 'Dummy Card Statement',
            'start_date' => '2026-08-20',
            'end_date' => null,
            'price' => 142.89,
            'currency' => 'EUR',
            'type' => 'one-time',
            'recurrence' => null,
            'status' => 'pending',
            'metadata' => [
                'vendor_name' => 'Dummy Vendor GmbH',
                'invoice_number' => null,
                'due_date' => '2026-08-20',
                'amount_due' => 142.89,
                'subtotal' => null,
                'tax' => null,
                'total' => 4763.03,
                'recurrence_status' => null,
                'recurrence_interval' => null,
                'next_invoice_date' => null,
            ],
        ]);

        $this->assertSame('Dummy Vendor GmbH', $attributes['title']);
        $this->assertSame('2026-08-20', $attributes['start_date']);
        $this->assertSame(142.89, $attributes['price']);
        $this->assertSame('EUR', $attributes['currency']);
        $this->assertSame('one-time', $attributes['type']);
        $this->assertNull($attributes['recurrence']);
        $this->assertSame('pending', $attributes['status']);
        $this->assertNull($attributes['end_date']);
    }
}
