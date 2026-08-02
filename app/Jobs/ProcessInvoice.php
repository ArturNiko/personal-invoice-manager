<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

use \App\Models\InvoiceImport;
use \App\Enums\InvoiceImportState;


class ProcessInvoice implements ShouldQueue
{
    use Dispatchable;
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

        try {
            $invoiceImport->update([
                'status' => InvoiceImportState::PROCESSING->value,
                'error_message' => null,
            ]);

            $invoiceImport->update([
                'status' => InvoiceImportState::COMPLETED->value,
            ]);
        } catch (Throwable $throwable) {
            $invoiceImport->update([
                'status' => InvoiceImportState::FAILED->value,
                'error_message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
