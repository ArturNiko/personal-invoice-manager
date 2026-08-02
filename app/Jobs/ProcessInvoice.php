<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use \App\Models\InvoiceImport;
use \App\Enums\InvoiceImportState;


class ProcessInvoice implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceImportId)
    {
        //
    }

    public function handle(): void
    {
        $invoiceImport = InvoiceImport::find($this->invoiceImportId);

        if (! $invoiceImport) {
            return;
        }

        $invoiceImport->update([
            'status' => InvoiceImportState::PROCESSING->value,
            'error_message' => null,
        ]);

        $invoiceImport->update([
            'status' => InvoiceImportState::COMPLETED->value,
        ]);
    }
}
