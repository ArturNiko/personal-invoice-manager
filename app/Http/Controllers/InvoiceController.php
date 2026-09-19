<?php

namespace App\Http\Controllers;

use App\Enums\AgentTaskState;
use App\Exceptions\InvoiceNotProcessableException;
use App\Http\Requests\InvoiceImportRequest;
use App\Http\Requests\InvoiceIndexRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\AgentTask;
use App\Models\Invoice;
use App\Services\Nanonets\NanonetsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(InvoiceIndexRequest $request): JsonResponse|Response
    {
        if (! $request->expectsJson()) {
            return response()->view('app');
        }

        $validated = $request->validated();

        $query = auth()->user()->invoices();

        $query->when(isset($validated['q']), function ($q) use ($validated) {
            $q->where('title', 'like', '%'.$validated['q'].'%');
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

    public function show(Request $request, Invoice $invoice): JsonResponse|Response
    {
        $this->authorizeAccess($invoice);

        if (!$request->expectsJson()) {
            return response()->view('app');
        }

        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }

    public function update(Invoice $invoice, UpdateInvoiceRequest $request)
    {
        $this->authorizeAccess($invoice);

        $validated = $request->validated();

        $invoice->update($validated);

        return response()->json($invoice);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    public function import(InvoiceImportRequest $request, NanonetsClient $nanonetsClient)
    {
        $file = $request->file('invoice');
        $path = $file->store('invoice-uploads');

        $agentTask = AgentTask::create([
            'user_id' => auth()->id(),
            'status' => AgentTaskState::PENDING->value,
            'file_path' => $path,
        ]);

        try {
            $submission = $nanonetsClient->predictStoredFile($path, 'agent_task:'.$agentTask->id);

            if ($nanonetsClient->looksLikeTaskEnvelope($submission)) {
                $agentTask->update([
                    'status' => AgentTaskState::PROCESSING->value,
                    'details' => ['nanonets_task_id' => $submission['task_id'] ?? null],
                ]);

                return response()->json([
                    'message' => 'Invoice uploaded and is being processed.',
                    'task_id' => $agentTask->id,
                    'status' => $agentTask->status,
                ], 202);
            }

            throw new InvoiceNotProcessableException('Nanonets did not return a queued task envelope. Polling-based processing is required.');
        } 
        catch (InvoiceNotProcessableException $exception) {
            $agentTask->update([
                'status' => AgentTaskState::FAILED->value,
                'details' => ['error' => $exception->getMessage()],
            ]);

            return response()->json([
                'message' => 'Invoice could not be processed.',
                'error' => $exception->getMessage(),
                'task_id' => $agentTask->id,
                'status' => $agentTask->status,
            ], 422);
        } 
        catch (\Throwable $throwable) {
            Log::error('Invoice import failed.', [
                'agent_task_id' => $agentTask->id,
                'message' => $throwable->getMessage(),
            ]);

            $agentTask->update([
                'status' => AgentTaskState::FAILED->value,
                'details' => ['error' => $throwable->getMessage()],
            ]);

            return response()->json([
                'message' => 'Invoice submission failed.',
                'error' => $throwable->getMessage(),
                'task_id' => $agentTask->id,
                'status' => $agentTask->status,
            ], 500);
        }
    }

    private function authorizeAccess(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 403);
    }

}
