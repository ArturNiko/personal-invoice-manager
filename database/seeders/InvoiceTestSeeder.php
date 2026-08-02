<?php

namespace Database\Seeders;

use App\Enums\InvoiceCurrency;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class InvoiceTestSeeder extends Seeder
{
    /**
     * Seed invoices with a small, realistic test set for the index page.
     */
    public function run(): void
    {
        $invoices = [
            [
                'title' => 'Website hosting',
                'status' => InvoiceStatus::PENDING->value,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => null,
                'price' => 24.99,
                'currency' => InvoiceCurrency::EUR->value,
                'type' => InvoiceType::RECURRING->value,
                'recurrence' => InvoiceReccuranceType::MONTHLY->value,
            ],
            [
                'title' => 'Logo redesign',
                'status' => InvoiceStatus::PAID->value,
                'start_date' => now()->subDays(12)->toDateString(),
                'end_date' => null,
                'price' => 680,
                'currency' => InvoiceCurrency::USD->value,
                'type' => InvoiceType::ONE_TIME->value,
                'recurrence' => null,
            ],
            [
                'title' => 'Cloud storage',
                'status' => InvoiceStatus::PENDING->value,
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => null,
                'price' => 12.5,
                'currency' => InvoiceCurrency::EUR->value,
                'type' => InvoiceType::RECURRING->value,
                'recurrence' => InvoiceReccuranceType::WEEKLY->value,
            ],
            [
                'title' => 'Consulting sprint',
                'status' => InvoiceStatus::OVERDUE->value,
                'start_date' => now()->subDays(4)->toDateString(),
                'end_date' => null,
                'price' => 1450,
                'currency' => InvoiceCurrency::GBP->value,
                'type' => InvoiceType::ONE_TIME->value,
                'recurrence' => null,
            ],
            [
                'title' => 'Analytics subscription',
                'status' => InvoiceStatus::PENDING->value,
                'start_date' => now()->addDays(8)->toDateString(),
                'end_date' => null,
                'price' => 79,
                'currency' => InvoiceCurrency::EUR->value,
                'type' => InvoiceType::RECURRING->value,
                'recurrence' => InvoiceReccuranceType::YEARLY->value,
            ],
            [
                'title' => 'One-off feature fix',
                'status' => InvoiceStatus::PAID->value,
                'start_date' => now()->subDays(18)->toDateString(),
                'end_date' => null,
                'price' => 230,
                'currency' => InvoiceCurrency::CHF->value,
                'type' => InvoiceType::ONE_TIME->value,
                'recurrence' => null,
            ],
            [
                'title' => 'Design system support',
                'status' => InvoiceStatus::PENDING->value,
                'start_date' => now()->addDays(12)->toDateString(),
                'end_date' => null,
                'price' => 300,
                'currency' => InvoiceCurrency::USD->value,
                'type' => InvoiceType::RECURRING->value,
                'recurrence' => InvoiceReccuranceType::WEEKLY->value,
            ],
            [
                'title' => 'Quarterly reporting',
                'status' => InvoiceStatus::OVERDUE->value,
                'start_date' => now()->subDays(1)->toDateString(),
                'end_date' => null,
                'price' => 420,
                'currency' => InvoiceCurrency::EUR->value,
                'type' => InvoiceType::RECURRING->value,
                'recurrence' => InvoiceReccuranceType::QUARTERLY->value,
            ],
        ];

        foreach ($invoices as $invoice) {
            Invoice::create($invoice);
        }
    }
}