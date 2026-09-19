<?php

namespace App\Console\Commands;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Console\Command;

class SyncInvoiceOccurrences extends Command
{
    protected $signature = 'app:sync-invoice-occurrences';

    protected $description = 'Sync recurring invoice occurrences with their schedule';

    public function handle(): int
    {
        Invoice::query()
            ->where('type', InvoiceType::RECURRING->value)
            ->orderBy('id')
            ->chunkById(100, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $invoice->syncOccurrences();
                }
            });

        $this->info('Recurring invoice occurrences synced successfully.');

        return self::SUCCESS;
    }
}