<?php

namespace App\Http\Controllers;

use App\Enums\AgentTaskState;
use App\Exceptions\InvoiceNotProcessableException;
use App\Http\Requests\InvoiceImportRequest;
use App\Http\Requests\InvoiceIndexRequest;
use App\Http\Requests\InvoicesRequest;
use App\Models\AgentTask;
use App\Models\Invoice;
use App\Services\Nanonets\NanonetsClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(InvoiceIndexRequest $request)
    {
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
        $validated['user_id'] = auth()->id();

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    public function import(InvoiceImportRequest $request, NanonetsClient $nanonetsClient)
    {
        $file = $request->file('invoice');
        $path = $file->store('invoice-uploads');

        $agentTask = AgentTask::create([
            'status' => AgentTaskState::PENDING->value,
            'file_path' => $path,
        ]);

        try {
            $submission = $nanonetsClient->submitForProcessing($path, 'agent_task:'.$agentTask->id);

            if ($submission['task_id']) {
                $agentTask->update([
                    'status' => AgentTaskState::PROCESSING->value,
                    'details' => ['nanonets_task_id' => $submission['task_id']],
                ]);
            } else {
                $invoiceData = $nanonetsClient->buildInvoiceAttributes($submission['result']);
                $invoiceData['user_id'] = auth()->id();

                $invoice = DB::transaction(function () use ($agentTask, $invoiceData, $submission) {
                    $invoice = Invoice::create($invoiceData);

                    $agentTask->update([
                        'status' => AgentTaskState::COMPLETED->value,
                        'details' => $submission['result'],
                        'invoice_id' => $invoice->id,
                    ]);

                    return $invoice;
                });

                return response()->json([
                    'message' => 'Invoice processed successfully.',
                    'invoice' => $invoice,
                    'task_id' => $agentTask->id,
                    'status' => $agentTask->status,
                ], 200);
            }

            return response()->json([
                'message' => 'Invoice received and queued for processing.',
                'task_id' => $agentTask->id,
                'status' => $agentTask->status,
            ], 202);
        } catch (InvoiceNotProcessableException $exception) {
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
        } catch (\Throwable $throwable) {
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

    protected function pollForResult(NanonetsClient $nanonetsClient, string $taskId): array
    {
        $maxAttempts = max(1, (int) config('services.nanonets.agent_poll_attempts', 12));
        $pollDelay = max(1, (int) config('services.nanonets.agent_poll_delay_seconds', 5));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $prediction = $nanonetsClient->fetchTaskResult($taskId);

            if (! $nanonetsClient->isStillProcessing($prediction)) {
                return $prediction;
            }

            if ($attempt < $maxAttempts) {
                sleep($pollDelay);
            }
        }

        throw new \RuntimeException(sprintf(
            'Nanonets agent task %s did not complete after %d attempts',
            $taskId, $maxAttempts
        ));
    }
}
