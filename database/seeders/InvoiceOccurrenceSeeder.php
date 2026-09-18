<?php

namespace Database\Seeders;

use App\Enums\InvoiceOccurrenceStatus;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class InvoiceOccurrenceSeeder extends Seeder
{
    /**
     * Seed recurring payment occurrences for each recurring invoice.
     */
    public function run(): void
    {
        $invoices = Invoice::query()->where('type', InvoiceType::RECURRING->value)->get();

        foreach ($invoices as $invoice) {
            $invoice->occurrences()->delete();

            $current = now()->startOfMonth();
            $count = 6;

            for ($i = 0; $i < $count; $i++) {
                $dueDate = match ($invoice->recurrence) {
                    InvoiceReccuranceType::WEEKLY->value => $current->copy()->addWeeks($i),
                    InvoiceReccuranceType::BIWEEKLY->value => $current->copy()->addWeeks($i * 2),
                    InvoiceReccuranceType::QUARTERLY->value => $current->copy()->addMonths($i * 3),
                    InvoiceReccuranceType::SEMIANNUAL->value => $current->copy()->addMonths($i * 6),
                    InvoiceReccuranceType::YEARLY->value => $current->copy()->addYears($i),
                    default => $current->copy()->addMonths($i),
                };

                $status = match ($i) {
                    0 => InvoiceOccurrenceStatus::PAID,
                    1 => InvoiceOccurrenceStatus::PENDING,
                    2 => InvoiceOccurrenceStatus::OVERDUE,
                    3 => InvoiceOccurrenceStatus::PENDING,
                    default => InvoiceOccurrenceStatus::PENDING,
                };

                $invoice->occurrences()->create([
                    'due_date' => $dueDate->toDateString(),
                    'amount' => (float) $invoice->price,
                    'currency' => $invoice->currency,
                    'status' => $status->value,
                    'paid_at' => $status === InvoiceOccurrenceStatus::PAID ? $dueDate->copy()->addDays(2)->toDateTimeString() : null,
                ]);
            }
        }
    }
}
