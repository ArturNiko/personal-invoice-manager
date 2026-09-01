<?php

namespace App\Services\Nanonets;

use App\Enums\InvoiceCurrency;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class NanonetsClient
{
    public function predictStoredFile(string $storedPath, ?string $requestMetadata = null): array
    {
        $apiKey = (string) config('services.nanonets.api_key');
        $agentId = (string) config('services.nanonets.agent_id');

        if ($apiKey === '' || $agentId === '') {
            throw new RuntimeException('Nanonets is not configured. Set NANONETS_API_KEY and NANONETS_AGENT_ID.');
        }

        $localPath = $this->resolveLocalPath($storedPath);
        $endpoint = config('services.nanonets.agent_run_url');

        Log::info('Uploading invoice file to Nanonets agent.', [
            'request_metadata' => $requestMetadata,
            'file_name' => basename($localPath),
            'agent_id' => $agentId,
            'endpoint' => $endpoint,
        ]);

        $payload = [
            'query' => 'Process this document',
            'request_metadata' => $requestMetadata,
        ];

        $response = Http::withToken($apiKey)
            ->attach('file', file_get_contents($localPath), basename($localPath))
            ->asMultipart()
            ->post($endpoint, array_filter($payload, static fn ($value) => $value !== null && $value !== ''));

        $response->throw();

        $payload = $response->json() ?? [];

        Log::info('Nanonets agent upload completed.', [
            'request_metadata' => $requestMetadata,
            'response_keys' => array_keys($payload),
        ]);

        if ($this->looksLikeTaskEnvelope($payload)) {
            return $this->pollAgentTaskResult($agentId, (string) ($payload['task_id'] ?? ''), $payload, $requestMetadata);
        }

        return $payload;
    }

    public function buildInvoiceAttributes(array $prediction): array
    {
        if ($this->looksLikeTaskEnvelope($prediction)) {
            throw new RuntimeException('Nanonets returned a queued task envelope instead of a final prediction payload.');
        }

        Log::info('Mapping invoice attributes from Nanonets payload.', [
            'payload_shape' => $this->describePayloadShape($prediction),
            'top_level_keys' => array_keys($prediction),
        ]);

        if ($this->looksLikeStructuredPayload($prediction)) {
            $attributes = $this->buildInvoiceAttributesFromStructuredPayload($prediction);

            Log::info('Structured invoice payload mapped.', $attributes);

            return $attributes;
        }

        $fields = $this->flattenPredictions($prediction);

        $type = $this->detectType($fields);
        $status = $this->normalizeStatus($fields['status'] ?? null);
        $currency = $this->normalizeCurrency($fields['currency'] ?? null);
        $startDate = $this->normalizeDate($fields['invoice_date'] ?? $fields['date'] ?? $fields['due_date'] ?? null)
            ?? now()->toDateString();
        $endDate = $type === InvoiceType::RECURRING->value
            ? $this->normalizeDate($fields['recurrence_end_date'] ?? null)
            : null;
        $title = $this->normalizeTitle($fields);
        $price = $this->normalizeAmount($this->selectPriceValue($fields, $type));
        $recurrence = $type === InvoiceType::RECURRING->value
            ? $this->normalizeRecurrence($fields['recurrence_interval'] ?? $fields['recurrence'] ?? null)
            : null;

        $attributes = [
            'title' => $title,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price' => $price ?? 0,
            'currency' => $currency,
            'type' => $type,
            'recurrence' => $recurrence,
        ];

        Log::info('Prediction payload mapped.', $attributes);

        return $attributes;
    }

    protected function pollAgentTaskResult(string $agentId, string $taskId, array $initialPayload, ?string $requestMetadata = null): array
    {
        $resultUrl = $this->resolveAgentResultUrl($agentId, $taskId);
        $pollAttempts = max(1, (int) config('services.nanonets.agent_poll_attempts', 12));
        $pollDelaySeconds = max(1, (int) config('services.nanonets.agent_poll_delay_seconds', 5));

        if ($resultUrl === null) {
            Log::warning('Nanonets agent result url is not configured.', [
                'request_metadata' => $requestMetadata,
                'task_id' => $taskId,
                'agent_id' => $agentId,
            ]);

            throw new RuntimeException(sprintf('Nanonets agent task %s was queued, but no result url is configured.', $taskId));
        }

        for ($attempt = 1; $attempt <= $pollAttempts; $attempt++) {
            Log::info('Polling Nanonets agent task result.', [
                'request_metadata' => $requestMetadata,
                'task_id' => $taskId,
                'agent_id' => $agentId,
                'attempt' => $attempt,
                'poll_attempts' => $pollAttempts,
                'result_url' => $resultUrl,
            ]);

            try {
                $response = Http::withToken((string) config('services.nanonets.api_key'))
                    ->acceptJson()
                    ->timeout(120)
                    ->get($resultUrl);
            } catch (\Throwable $throwable) {
                Log::warning('Nanonets agent result poll failed.', [
                    'request_metadata' => $requestMetadata,
                    'task_id' => $taskId,
                    'agent_id' => $agentId,
                    'attempt' => $attempt,
                    'error' => $throwable->getMessage(),
                ]);

                sleep($pollDelaySeconds);
                continue;
            }

            if (! $response->successful()) {
                Log::warning('Nanonets agent result poll returned non-successful response.', [
                    'request_metadata' => $requestMetadata,
                    'task_id' => $taskId,
                    'agent_id' => $agentId,
                    'attempt' => $attempt,
                    'status' => $response->status(),
                ]);

                sleep($pollDelaySeconds);
                continue;
            }

            $payload = $response->json() ?? [];

            if ($this->looksLikeTaskEnvelope($payload)) {
                $status = strtolower(trim((string) ($payload['status'] ?? '')));

                if (in_array($status, ['queued', 'pending', 'processing', 'running', 'in_progress'], true)) {
                    sleep($pollDelaySeconds);
                    continue;
                }
            }

            if ($this->isFinalAgentPayload($payload)) {
                Log::info('Nanonets agent task completed.', [
                    'request_metadata' => $requestMetadata,
                    'task_id' => $taskId,
                    'agent_id' => $agentId,
                    'attempt' => $attempt,
                    'payload_keys' => array_keys($payload),
                ]);

                return $payload;
            }

            sleep($pollDelaySeconds);
        }

        throw new RuntimeException(sprintf('Nanonets agent task %s did not reach a final result after %d attempts.', $taskId, $pollAttempts));
    }

    protected function resolveAgentResultUrl(string $agentId, string $taskId): ?string
    {
        $configured = trim((string) config('services.nanonets.agent_result_url', ''));

        if ($configured !== '') {
            $replacements = [
                '{task_id}' => $taskId,
                '{agent_id}' => $agentId,
                ':task_id' => $taskId,
                ':agent_id' => $agentId,
                '%task_id%' => $taskId,
                '%agent_id%' => $agentId,
            ];

            return strtr($configured, $replacements);
        }

        $baseUrl = rtrim((string) config('services.nanonets.agent_base_url', 'https://agents.nanonets.com/api'), '/');

        return $baseUrl . '/v1/tasks/' . $taskId;
    }

    protected function isFinalAgentPayload(array $payload): bool
    {
        return ! $this->looksLikeTaskEnvelope($payload)
            && (
                $this->looksLikeStructuredPayload($payload)
                || isset($payload['prediction'])
                || isset($payload['result'])
                || isset($payload['data'])
            );
    }

    protected function describePayloadShape(array $payload): string
    {
        if ($this->looksLikeTaskEnvelope($payload)) {
            return 'task-envelope';
        }

        if ($this->looksLikeStructuredPayload($payload)) {
            return 'structured';
        }

        if (isset($payload['result'])) {
            return 'result-wrapper';
        }

        if (isset($payload['prediction'])) {
            return 'prediction-wrapper';
        }

        return 'prediction';
    }

    protected function buildInvoiceAttributesFromStructuredPayload(array $payload): array
    {
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        $fields = array_merge($metadata, $payload);

        $type = $this->detectType($fields);
        $status = $this->normalizeStatus($payload['status'] ?? $metadata['status'] ?? null);
        $currency = $this->normalizeCurrency($payload['currency'] ?? $metadata['currency'] ?? null);
        $startDate = $this->normalizeDate($payload['start_date'] ?? $metadata['due_date'] ?? $metadata['invoice_date'] ?? null)
            ?? now()->toDateString();
        $endDate = $type === InvoiceType::RECURRING->value
            ? $this->normalizeDate($payload['end_date'] ?? $metadata['recurrence_end_date'] ?? null)
            : null;
        $title = (string) ($metadata['vendor_name'] ?? $payload['title'] ?? $metadata['invoice_number'] ?? 'Imported invoice');
        $price = $this->normalizeAmount($payload['price'] ?? $metadata['amount_due'] ?? $metadata['total'] ?? null);
        $recurrence = $type === InvoiceType::RECURRING->value
            ? $this->normalizeRecurrence($payload['recurrence'] ?? $metadata['recurrence_interval'] ?? null)
            : null;

        return [
            'title' => $title,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price' => $price ?? 0,
            'currency' => $currency,
            'type' => $type,
            'recurrence' => $recurrence,
        ];
    }

    protected function flattenPredictions(array $prediction): array
    {
        $fields = [];

        foreach (($prediction['result'] ?? []) as $pageResult) {
            foreach (($pageResult['prediction'] ?? []) as $item) {
                $label = $this->normalizeLabel($item['label'] ?? '');

                if ($label === '') {
                    continue;
                }

                $value = $this->extractPredictionValue($item);

                if ($value === null || $value === '') {
                    continue;
                }

                $fields[$label] ??= $value;
            }
        }

        return $fields;
    }

    protected function extractPredictionValue(array $item): ?string
    {
        $value = $item['ocr_text'] ?? $item['text'] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function normalizeLabel(string $label): string
    {
        $label = Str::of($label)->lower()->trim()->replace(['-', ' '], '_')->value();

        return match ($label) {
            'invoice_no', 'invoice_number', 'invoice#' => 'invoice_number',
            'invoice_date', 'date' => 'invoice_date',
            'due_date', 'payment_due_date' => 'due_date',
            'amount_due', 'minimum_amount_due', 'minimum_payment_due', 'payment_due_amount' => 'amount_due',
            'subtotal', 'sub_total' => 'subtotal',
            'tax', 'vat' => 'tax',
            'total', 'invoice_total' => 'total',
            'vendor', 'vendor_name', 'supplier', 'company_name' => 'vendor_name',
            'recurrence', 'billing_cycle' => 'recurrence_interval',
            'recurrence_status', 'is_recurring' => 'recurrence_status',
            'next_invoice_date', 'next_billing_date' => 'next_invoice_date',
            'recurrence_end_date', 'end_date' => 'recurrence_end_date',
            default => $label,
        };
    }

    protected function looksLikeStructuredPayload(array $payload): bool
    {
        return array_key_exists('metadata', $payload)
            || array_key_exists('start_date', $payload)
            || array_key_exists('price', $payload)
            || array_key_exists('title', $payload);
    }

    protected function looksLikeTaskEnvelope(array $payload): bool
    {
        return array_key_exists('task_id', $payload)
            && array_key_exists('status', $payload)
            && ! array_key_exists('prediction', $payload)
            && ! array_key_exists('result', $payload)
            && ! array_key_exists('data', $payload);
    }

    protected function selectPriceValue(array $fields, string $type): mixed
    {
        $statementLike = $type === InvoiceType::ONE_TIME->value
            && $this->looksLikeCardStatement($fields);

        if ($statementLike) {
            return $fields['amount_due']
                ?? $fields['payment_terms_amount_due']
                ?? $fields['price']
                ?? $fields['total']
                ?? $fields['invoice_total']
                ?? $fields['amount']
                ?? $fields['recurrence_price']
                ?? null;
        }

        return $fields['price']
            ?? $fields['total']
            ?? $fields['invoice_total']
            ?? $fields['amount']
            ?? $fields['recurrence_price']
            ?? null;
    }

    protected function looksLikeCardStatement(array $fields): bool
    {
        $type = strtolower(trim((string) ($fields['type'] ?? '')));
        $title = strtolower(trim((string) ($fields['title'] ?? '')));
        $paymentTerms = strtolower(trim((string) ($fields['payment_terms'] ?? '')));
        $notes = strtolower(trim((string) ($fields['invoice_notes'] ?? '')));

        return str_contains($type, 'credit card')
            || str_contains($title, 'mastercard')
            || str_contains($title, 'credit card')
            || str_contains($paymentTerms, 'mindestbetrag')
            || str_contains($paymentTerms, 'minimum amount due')
            || str_contains($notes, 'kreditkartenkonto')
            || str_contains($notes, 'card statement');
    }

    protected function normalizeTitle(array $fields): string
    {
        return (string) ($fields['vendor_name']
            ?? $fields['invoice_number']
            ?? 'Imported invoice');
    }

    protected function normalizeCurrency(mixed $value): string
    {
        $currency = strtoupper(trim((string) $value));

        if ($currency === '' || ! InvoiceCurrency::isValid($currency)) {
            return config('invoices.default_currency', InvoiceCurrency::EUR->value);
        }

        return $currency;
    }

    protected function normalizeStatus(mixed $value): string
    {
        $status = strtolower(trim((string) $value));

        if ($status === '' || ! InvoiceStatus::isValid($status)) {
            return InvoiceStatus::PENDING->value;
        }

        return $status;
    }

    protected function detectType(array $fields): string
    {
        $recurrenceStatus = strtolower(trim((string) ($fields['recurrence_status'] ?? '')));
        $recurrenceInterval = strtolower(trim((string) ($fields['recurrence_interval'] ?? '')));

        if (
            in_array($recurrenceStatus, ['recurring', 'repeat', 'repeating', 'monthly', 'weekly', 'biweekly', 'quarterly', 'semiannual', 'yearly'], true)
            || $this->normalizeRecurrence($recurrenceInterval) !== null
        ) {
            return InvoiceType::RECURRING->value;
        }

        return InvoiceType::ONE_TIME->value;
    }

    protected function normalizeRecurrence(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'weekly' => InvoiceReccuranceType::WEEKLY->value,
            'biweekly', 'bi-weekly', 'fortnightly' => InvoiceReccuranceType::BIWEEKLY->value,
            'monthly', 'every month' => InvoiceReccuranceType::MONTHLY->value,
            'quarterly', 'every quarter' => InvoiceReccuranceType::QUARTERLY->value,
            'semiannual', 'semi-annually', 'semi-annually', 'every 6 months' => InvoiceReccuranceType::SEMIANNUAL->value,
            'yearly', 'annual', 'annually', 'every year' => InvoiceReccuranceType::YEARLY->value,
            default => null,
        };
    }

    protected function normalizeDate(mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeAmount(mixed $value): ?float
    {
        if ($value === null) return null;


        if (is_int($value) || is_float($value)) return (float) $value;

        $raw = trim((string) $value);

        if ($raw === '') return null;

        $raw = preg_replace('/[^0-9,.-]/', '', $raw) ?? '';

        if ($raw === '') return null;

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace(',', '', $raw);
        } elseif (str_contains($raw, ',') && ! str_contains($raw, '.')) {
            $raw = str_replace(',', '.', $raw);
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    protected function resolveLocalPath(string $storedPath): string
    {
        if (is_file($storedPath)) {
            return $storedPath;
        }

        if (Storage::exists($storedPath)) {
            return Storage::path($storedPath);
        }

        throw new RuntimeException(sprintf('Invoice import file not found: %s', $storedPath));
    }
}