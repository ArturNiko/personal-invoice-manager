<?php

namespace App\Providers;

use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $apiKey = config('services.nanonets.api_key', '');
        $agentId = config('services.nanonets.agent_id', '');
        $agentUrl = config('services.nanonets.agent_url', '');

        // Allow app boot without Nanonets when nothing is configured.
        if ($apiKey === '' || $agentId === '') {
            throw new InvalidArgumentException(
                'Nanonets is partially configured. Set both NANONETS_API_KEY and NANONETS_AGENT_ID, or leave both empty.'
            );
        }
        if ($agentUrl === '') {
            throw new InvalidArgumentException(
                'Nanonets agent URL is not configured. Set NANONETS_AGENT_URL.'
            );
        }

        $this->validateNanonetsConnection($apiKey, $agentUrl);
    }

    private function validateNanonetsConnection(string $apiKey, string $baseUrl): void
    {
        if (!config('services.nanonets.validate_connection_on_boot', false)) return;

        $cacheSeconds = max(0, (int) config('services.nanonets.connection_check_cache_seconds', 300));
        $cacheKey = 'nanonets.connection.validation';

        if ($cacheSeconds > 0 && Cache::has($cacheKey)) return;

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(5)
                ->get($baseUrl);
        } 
        catch (\Throwable $throwable) {
            throw new InvalidArgumentException(
                sprintf('Unable to connect to Nanonets at "%s": %s', $baseUrl, $throwable->getMessage())
            );
        }

        if (! $response->successful()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Nanonets connection check failed for "%s" with HTTP %d.',
                    $baseUrl,
                    $response->status(),
                )
            );
        }

        if ($cacheSeconds > 0) {
            Cache::put($cacheKey, true, now()->addSeconds($cacheSeconds));
        }

        Log::info('Nanonets connection validated successfully.', [
            'base_url' => $baseUrl,
            'cache_seconds' => $cacheSeconds,
        ]);
    }
}
