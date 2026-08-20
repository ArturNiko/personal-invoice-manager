<?php

namespace App\Jobs;

use App\Enums\InvoiceImportState;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\InvoiceImport;
use App\Services\Nanonets\NanonetsClient;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;


class ProcessInvoice implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(public int $invoiceImportId)
    {
        //
    }

    public function handle(NanonetsClient $nanonetsClient): void
    {
        $invoiceImport = InvoiceImport::find($this->invoiceImportId);

        if (!$invoiceImport) return;

        try {
            $invoiceImport->update([
                'status' => InvoiceImportState::PROCESSING->value,
                'error_message' => null,
            ]);

            $prediction = $nanonetsClient->predictStoredFile(
                $invoiceImport->file_path,
                'invoice_import:' . $invoiceImport->id,
            );

            $invoiceData = $nanonetsClient->buildInvoiceAttributes($prediction);

            Invoice::create($invoiceData);

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
