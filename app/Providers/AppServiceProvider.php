<?php

namespace App\Providers;

use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;

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
    }
}
