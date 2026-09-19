<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceOccurrenceStatus;
use App\Models\Invoice;
use App\Models\InvoiceOccurrence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceOccurrenceController extends Controller
{
    public function index(Invoice $invoice): JsonResponse
    {
        $this->authorizeAccess($invoice);

        $invoice->syncOccurrences();

        return response()->json(
            $invoice->occurrences()->orderBy('due_date')->get()
        );
    }

    public function update(Request $request, Invoice $invoice, InvoiceOccurrence $occurrence): JsonResponse
    {
        $this->authorizeAccess($invoice);

        if ((int) $occurrence->invoice_id !== (int) $invoice->id) {
            return response()->json(['message' => 'Occurrence does not belong to this invoice.'], 404);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:'.implode(',', InvoiceOccurrenceStatus::getAll())],
            'paid_at' => ['nullable', 'date'],
        ]);

        if (isset($validated['status'])) {
            $occurrence->status = $validated['status'];

            if ($validated['status'] === InvoiceOccurrenceStatus::PAID->value) {
                $occurrence->paid_at = $validated['paid_at'] ?? now();
            } elseif ($validated['status'] !== InvoiceOccurrenceStatus::PAID->value) {
                $occurrence->paid_at = null;
            }
        }

        if (isset($validated['paid_at'])) {
            $occurrence->paid_at = $validated['paid_at'];
        }

        $occurrence->save();

        return response()->json($occurrence->fresh());
    }

    private function authorizeAccess(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 403);
    }
}
