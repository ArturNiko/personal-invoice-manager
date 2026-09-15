<?php

namespace App\Console\Commands;

use App\Enums\AgentTaskState;
use App\Models\AgentTask;
use App\Models\Invoice;
use App\Services\Nanonets\NanonetsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PollNanonetsTasks extends Command
{
    protected $signature = 'app:poll-nanonets-tasks';

    protected $description = 'Poll running Nanonets agent tasks and finalize completed invoices';

    public function handle(NanonetsClient $nanonetsClient): int
    {
        $this->logAgentTaskOverview($nanonetsClient);

        $agentTasks = AgentTask::where('status', AgentTaskState::PROCESSING->value)
            ->get()
            ->filter(function (AgentTask $agentTask) {
                $details = is_array($agentTask->details) ? $agentTask->details : [];

                return isset($details['nanonets_task_id']) && is_string($details['nanonets_task_id']);
            })
            ->values();

        if ($agentTasks->isEmpty()) {
            $this->info('No running Nanonets tasks to poll.');

            return self::SUCCESS;
        }

        $completed = 0;
        $failed = 0;

        foreach ($agentTasks as $agentTask) {
            $nanonetsTaskId = $agentTask->details['nanonets_task_id'];

            try {
                $status = $nanonetsClient->getTaskStatus($nanonetsTaskId);

                $taskStatus = strtolower((string) ($status['status'] ?? ''));

                if (in_array($taskStatus, ['pending', 'queued', 'running', 'waiting_for_input', 'awaiting_review', 'scheduled'], true)) {
                    $this->line(sprintf('Agent task #%s still %s, skipping.', $agentTask->id, $taskStatus));

                    continue;
                }

                if (in_array($taskStatus, ['failed', 'stopped'], true)) {
                    $agentTask->update([
                        'status' => AgentTaskState::FAILED->value,
                        'details' => array_merge(is_array($agentTask->details) ? $agentTask->details : [], [
                            'nanonets_status' => $taskStatus,
                        ]),
                    ]);

                    $failed++;

                    $this->warn(sprintf('Agent task #%s reached Nanonets status "%s".', $agentTask->id, $taskStatus));

                    continue;
                }

                if ($taskStatus !== 'completed') {
                    $this->line(sprintf('Agent task #%s has unknown status "%s", skipping.', $agentTask->id, $taskStatus));

                    continue;
                }

                $this->processCompletedTask($agentTask, $nanonetsTaskId, $nanonetsClient);

                $completed++;
            } catch (Throwable $throwable) {
                Log::error('Nanonets task poll failed.', [
                    'agent_task_id' => $agentTask->id,
                    'nanonets_task_id' => $nanonetsTaskId,
                    'error' => $throwable->getMessage(),
                ]);

                $this->error(sprintf('Agent task #%s failed to poll: %s', $agentTask->id, $throwable->getMessage()));
            }
        }

        $this->info(sprintf('Polling finished. %d completed, %d failed.', $completed, $failed));

        return self::SUCCESS;
    }

    protected function processCompletedTask(AgentTask $agentTask, string $nanonetsTaskId, NanonetsClient $nanonetsClient): void
    {
        $prediction = $nanonetsClient->fetchStructuredResult($nanonetsTaskId);

        $invoiceData = $nanonetsClient->buildInvoiceAttributes($prediction);
        $invoiceData['user_id'] = $agentTask->user_id;

        DB::transaction(function () use ($agentTask, $invoiceData, $prediction) {
            $invoice = Invoice::create($invoiceData);

            $agentTask->update([
                'status' => AgentTaskState::COMPLETED->value,
                'details' => $prediction,
                'invoice_id' => $invoice->id,
            ]);
        });

        $this->info(sprintf('Agent task #%s completed, invoice created.', $agentTask->id));

        Log::info('Nanonets task completed via poll.', [
            'agent_task_id' => $agentTask->id,
            'nanonets_task_id' => $agentTask->details['nanonets_task_id'] ?? $nanonetsTaskId,
        ]);
    }

    protected function logAgentTaskOverview(NanonetsClient $nanonetsClient): void
    {
        try {
            $payload = $nanonetsClient->listTasks();

            $tasks = $payload['data'] ?? [];

            $counts = [];

            foreach ($tasks as $task) {
                $status = strtolower((string) ($task['status'] ?? 'unknown'));
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }

            if ($counts === []) {
                $this->line('Nanonets agent has no tasks to report.');

                return;
            }

            $summary = collect($counts)
                ->map(fn (int $count, string $status) => sprintf('%s: %d', $status, $count))
                ->implode(', ');

            $this->line(sprintf('Nanonets agent task overview: %s.', $summary));
        } catch (Throwable $throwable) {
            Log::warning('Nanonets task list fetch failed.', [
                'error' => $throwable->getMessage(),
            ]);

            $this->warn(sprintf('Could not fetch Nanonets task overview: %s', $throwable->getMessage()));
        }
    }
}