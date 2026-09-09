<?php

namespace App\Services;

use App\Enums\InvoiceCurrency;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange_rates';

    /**
     * Approximate EUR-based rates used whenever the remote provider is
     * unreachable or does not cover one of the supported currencies.
     */
    private const FALLBACK_RATES = [
        'USD' => 1.08,
        'EUR' => 1.0,
        'GBP' => 0.84,
        'JPY' => 165.0,
        'AUD' => 1.64,
        'CAD' => 1.47,
        'CHF' => 0.94,
        'CNY' => 7.7,
        'SEK' => 11.3,
        'NZD' => 1.77,
        'RUB' => 100.0,
        'AMD' => 430.0,
    ];

    public function rates(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && $this->isFresh($cached)) {
            return $cached;
        }

        $fresh = $this->fetchFresh();

        if ($fresh !== null) {
            Cache::put(
                self::CACHE_KEY,
                $fresh,
                (int) config('services.exchange_rates.ttl_seconds', 86400),
            );

            return $fresh;
        }

        // The provider was unreachable: fall back to the stale copy when one
        // exists, otherwise to the bundled approximation table.
        return is_array($cached) ? $cached : $this->fallbackPayload();
    }

    private function fetchFresh(): ?array
    {
        try {
            $response = Http::timeout(10)->get(
                config('services.exchange_rates.provider', 'https://api.frankfurter.app/latest'),
                ['from' => 'EUR'],
            );

            if (!$response->ok()) {
                Log::warning('Exchange rate fetch failed.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $apiRates = $response->json('rates');

            if (!is_array($apiRates)) {
                Log::warning('Exchange rate provider returned an invalid payload.');

                return null;
            }

            $rates = [];

            foreach (InvoiceCurrency::getAll() as $currency) {
                $value = $apiRates[$currency] ?? null;

                // Empty, unknown or temporarily unavailable rates fall back to
                // the bundled approximation table (e.g. null from the provider).
                if (!is_numeric($value) || (float) $value <= 0) {
                    $value = self::FALLBACK_RATES[$currency] ?? null;
                }

                $rates[$currency] = $value === null
                    ? null
                    : (float) $value;
            }

            return [
                'base' => 'EUR',
                'rates' => $rates,
                'updated_at' => CarbonImmutable::now()->toIso8601String(),
            ];
        } catch (\Throwable $throwable) {
            Log::warning('Exchange rate fetch failed.', [
                'error' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    private function isFresh(array $payload): bool
    {
        $updatedAt = $payload['updated_at'] ?? null;

        if (!is_string($updatedAt)) {
            return false;
        }

        $updated = strtotime($updatedAt);

        if ($updated === false) {
            return false;
        }

        return $updated + (int) config('services.exchange_rates.ttl_seconds', 86400) > time();
    }

    private function fallbackPayload(): array
    {
        return [
            'base' => 'EUR',
            'rates' => self::FALLBACK_RATES,
            'updated_at' => null,
        ];
    }
}
