<?php

namespace App\Providers;

use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use App\Enums\InvoiceCurrency;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $supportedCurrencies = InvoiceCurrency::getAll();
        $defaultCurrency = strtoupper((string) config('invoices.default_currency', InvoiceCurrency::EUR->value));

        config(['invoices.default_currency' => $defaultCurrency]);

        if (!InvoiceCurrency::isValid($defaultCurrency)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid INVOICE_DEFAULT_CURRENCY "%s". Allowed values: %s',
                    $defaultCurrency,
                    implode(', ', $supportedCurrencies)
                )
            );
        }

        $this->validateNanonetsConfiguration();
    }

    private function validateNanonetsConfiguration(): void
    {
        $apiKey = trim((string) config('services.nanonets.api_key', ''));
        $modelId = trim((string) config('services.nanonets.model_id', ''));
        $baseUrl = trim((string) config('services.nanonets.base_url', ''));

        // Allow app boot without Nanonets when nothing is configured.
        if ($apiKey === '' && $modelId === '') {
            return;
        }

        if ($apiKey === '' || $modelId === '') {
            throw new InvalidArgumentException(
                'Nanonets is partially configured. Set both NANONETS_API_KEY and NANONETS_MODEL_ID, or leave both empty.'
            );
        }

        if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL) || !str_starts_with($baseUrl, 'https://')) {
            throw new InvalidArgumentException(
                sprintf('Invalid NANONETS_BASE_URL "%s". Expected a valid HTTPS URL.', $baseUrl)
            );
        }

        $this->validateNanonetsConnection($apiKey, $baseUrl);
    }

    private function validateNanonetsConnection(string $apiKey, string $baseUrl): void
    {
        if (!config('services.nanonets.validate_connection_on_boot', false)) return;

        $cacheSeconds = max(0, (int) config('services.nanonets.connection_check_cache_seconds', 300));
        $cacheKey = 'nanonets.connection.validation';

        if ($cacheSeconds > 0 && Cache::has($cacheKey)) return;

        $healthUrl = $this->buildNanonetsHealthUrl($baseUrl);

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->acceptJson()
                ->timeout(5)
                ->get($healthUrl);
        } 
        catch (\Throwable $throwable) {
            throw new InvalidArgumentException(
                sprintf('Unable to connect to Nanonets at "%s": %s', $healthUrl, $throwable->getMessage())
            );
        }

        if (! $response->successful()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Nanonets connection check failed for "%s" with HTTP %d.',
                    $healthUrl,
                    $response->status(),
                )
            );
        }

        if ($cacheSeconds > 0) {
            Cache::put($cacheKey, true, now()->addSeconds($cacheSeconds));
        }
    }

    private function buildNanonetsHealthUrl(string $baseUrl): string
    {
        $trimmedBaseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($trimmedBaseUrl, '/api/v2')) {
            return str_replace('/api/v2', '/v2/', $trimmedBaseUrl);
        }

        return $trimmedBaseUrl . '/';
    }
}
