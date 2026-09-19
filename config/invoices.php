<?php

use App\Enums\InvoiceCurrency;

return [
    'default_currency' => env('INVOICE_DEFAULT_CURRENCY', InvoiceCurrency::EUR->value),
    'landing_path' => env('APP_LANDING_PATH', '/dashboard'),
    'occurrence_future_window' => (int) env('INVOICE_OCCURRENCE_FUTURE_WINDOW', 6),
];
