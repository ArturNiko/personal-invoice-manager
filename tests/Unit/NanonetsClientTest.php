<?php

namespace Tests\Unit;

use App\Services\Nanonets\NanonetsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NanonetsClientTest extends TestCase
{
    public function test_it_posts_to_the_nanonets_agent_run_endpoint(): void
    {
        config()->set('services.nanonets.api_key', 'test-key');
        config()->set('services.nanonets.agent_id', 'agent-123');
        config()->set('services.nanonets.agent_base_url', 'https://agents.nanonets.com/api');
        config()->set('services.nanonets.agent_run_url', 'https://agents.nanonets.com/api/v1/agents/agent-123/run');
        config()->set('services.nanonets.agent_result_url', 'https://agents.nanonets.com/api/v1/tasks/{task_id}');

        Http::fake([
            'https://agents.nanonets.com/api/v1/agents/agent-123/run' => Http::response([
                'task_id' => 'task-123',
                'agent_id' => 'agent-123',
                'status' => 'queued',
                'message' => 'Task queued',
                'created_at' => '2026-08-20T10:00:00Z',
            ], 200),
            'https://agents.nanonets.com/api/v1/tasks/task-123' => Http::response([
                'title' => 'Dummy Vendor GmbH',
                'start_date' => '2026-08-20',
                'price' => 142.89,
                'currency' => 'EUR',
                'type' => 'one-time',
                'recurrence' => null,
                'status' => 'pending',
                'metadata' => [
                    'vendor_name' => 'Dummy Vendor GmbH',
                    'amount_due' => 142.89,
                ],
            ], 200),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'nanonets-agent-test');
        file_put_contents($path, 'dummy pdf');

        try {
            $client = new NanonetsClient;
            $response = $client->predictStoredFile($path, 'invoice_import:1');

            $this->assertSame('Dummy Vendor GmbH', $response['title']);
            Http::assertSentCount(2);

            Http::assertSent(function ($request) {
                return $request->url() === 'https://agents.nanonets.com/api/v1/agents/agent-123/run'
                    && $request->method() === 'POST'
                    && $request->hasHeader('Authorization', 'Bearer test-key');
            });

            Http::assertSent(function ($request) {
                return $request->url() === 'https://agents.nanonets.com/api/v1/tasks/task-123'
                    && $request->method() === 'GET'
                    && $request->hasHeader('Authorization', 'Bearer test-key');
            });
        } finally {
            @unlink($path);
        }
    }

    public function test_it_maps_structured_credit_card_statement_payloads(): void
    {
        $client = new NanonetsClient;

        $attributes = $client->buildInvoiceAttributes([
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
