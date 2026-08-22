<?php

namespace App\Jobs;

use App\Enums\AgentTaskState;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Services\Nanonets\NanonetsClient;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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

        if (! $invoiceImport) {
            Log::warning('Invoice import not found.', [
                'invoice_import_id' => $this->invoiceImportId,
            ]);

            return;
        }

        try {
            Log::info('Invoice import started.', [
                'invoice_import_id' => $invoiceImport->id,
                'file_path' => $invoiceImport->file_path,
            ]);

            $invoiceImport->update([
                'status' => AgentTaskState::PROCESSING->value,
                'error_message' => null,
            ]);

            Log::info('Sending invoice file to Nanonets.', [
                'invoice_import_id' => $invoiceImport->id,
            ]);

            $prediction = $nanonetsClient->predictStoredFile(
                $invoiceImport->file_path,
                'invoice_import:' . $invoiceImport->id,
            );

            Log::info('Nanonets prediction received.', [
                'invoice_import_id' => $invoiceImport->id,
                'prediction_keys' => array_keys($prediction),
            ]);

            $invoiceData = $nanonetsClient->buildInvoiceAttributes($prediction);

            Log::info('Invoice attributes mapped.', [
                'invoice_import_id' => $invoiceImport->id,
                'title' => $invoiceData['title'] ?? null,
                'start_date' => $invoiceData['start_date'] ?? null,
                'end_date' => $invoiceData['end_date'] ?? null,
                'price' => $invoiceData['price'] ?? null,
                'currency' => $invoiceData['currency'] ?? null,
                'type' => $invoiceData['type'] ?? null,
                'recurrence' => $invoiceData['recurrence'] ?? null,
            ]);

            Invoice::create($invoiceData);

            $invoiceImport->update([
                'status' => AgentTaskState::COMPLETED->value,
            ]);

            Log::info('Invoice import completed.', [
                'invoice_import_id' => $invoiceImport->id,
            ]);
        } catch (Throwable $throwable) {
            Log::error('Invoice import failed.', [
                'invoice_import_id' => $invoiceImport->id,
                'message' => $throwable->getMessage(),
            ]);

            $invoiceImport->update([
                'status' => AgentTaskState::FAILED->value,
                'error_message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
