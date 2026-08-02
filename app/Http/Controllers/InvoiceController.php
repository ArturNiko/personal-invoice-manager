<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceImportState;
use App\Http\Requests\InvoiceImportRequest;
use App\Jobs\ProcessInvoice;
use App\Models\Invoice;
use App\Models\InvoiceImport;
use App\Http\Requests\InvoiceIndexRequest;
use App\Http\Requests\InvoicesRequest;


class InvoiceController extends Controller
{
    public function index(InvoiceIndexRequest $request)
    {
        $validated = $request->validated();

        $query = Invoice::query();

        $query->when(isset($validated['q']), function ($q) use ($validated) {
            $q->where('title', 'like', '%' . $validated['q'] . '%');
        });

        $query->when(isset($validated['status']), function ($q) use ($validated) {
            $q->where('status', $validated['status']);
        });

        $query->when(isset($validated['type']), function ($q) use ($validated) {
            $q->where('type', $validated['type']);
        });

        $query->when(isset($validated['recurrence']), function ($q) use ($validated) {
            $q->where('recurrence', $validated['recurrence']);
        });

        $sort = $validated['sort'] ?? 'start_date';
        $direction = $validated['direction'] ?? 'asc';
        $perPage = $validated['per_page'] ?? 15;

        $invoices = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($invoices);
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }

    public function update(Invoice $invoice, InvoicesRequest $request)
    {
        $validated = $request->validated();

        $invoice->update($validated);

        return response()->json($invoice);
    }

    public function store(InvoicesRequest $request)
    {
        $validated = $request->validated();

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    public function import(InvoiceImportRequest $request)
    {
        $file = $request->file('invoice');

        $path = $file->store('invoice-uploads');

        $invoiceImport = InvoiceImport::create([
            'file_path' => $path,
            'status' => InvoiceImportState::PENDING->value,
        ]);

        ProcessInvoice::dispatch($invoiceImport->id);

        return response()->json([
            'message' => 'Invoice is being processed'
        ]);
    }
}
