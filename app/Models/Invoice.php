<?php

namespace App\Models;

use App\Enums\AgentTaskState;
use App\Enums\InvoiceOccurrenceStatus;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Invoice model
 *
 * @property int $id
 * @property string $title
 * @property Carbon $start_date // start date for the invoice, used for one-time invoices and recurring invoices
 * @property Carbon|null $end_date // optional end date for recurring invoices
 * @property float|null $price // invoice amount
 * @property string $currency // currency enum: 'EUR', 'USD', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'NZD', 'CNY', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'RUB', 'BRL', 'INR'
 * @property string $type // type enum: 'one-time', 'recurring'
 * @property string|null $recurrence // recurrence enum: 'weekly', 'biweekly', 'monthly', 'quarterly', 'semiannual', 'yearly'
 * @property string $status // status enum: 'pending', 'paid', 'overdue'
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'start_date',
        'end_date',
        'price',
        'currency',
        'type',
        'recurrence',
        'status',
    ];

    protected static function booted(): void
    {
        static::created(function (self $invoice): void {
            $invoice->syncOccurrences();
        });

        static::updated(function (self $invoice): void {
            if ($invoice->type !== InvoiceType::RECURRING->value) {
                return;
            }

            if ($invoice->wasChanged(['end_date', 'price', 'currency'])) {
                $invoice->syncOccurrences();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function occurrences()
    {
        return $this->hasMany(InvoiceOccurrence::class);
    }

    public function generateOccurrencesIfNeeded(): void
    {
        $this->syncOccurrences();
    }

    public function syncOccurrences(): void
    {
        if ($this->type !== InvoiceType::RECURRING->value) {
            return;
        }

        $today = now()->startOfDay();
        $endDate = $this->normalizedEndDate();

        $this->markOverdueOccurrences($today);

        if ($endDate !== null) {
            $this->seedOccurrencesUntil($endDate);

            return;
        }

        $futureOccurrenceCount = $this->occurrences()
            ->whereDate('due_date', '>=', $today->toDateString())
            ->count();

        $nextDueDate = $this->nextDueDate();

        $futureWindow = max(0, (int) config('invoices.occurrence_future_window', 6));

        while ($futureOccurrenceCount < $futureWindow) {
            if (! $this->occurrences()->whereDate('due_date', $nextDueDate->toDateString())->exists()) {
                $this->createOccurrence($nextDueDate);

                if ($nextDueDate->greaterThanOrEqualTo($today)) {
                    $futureOccurrenceCount++;
                }
            }

            $nextDueDate = $this->advanceDueDate($nextDueDate);
        }
    }

    private function seedOccurrencesUntil(Carbon $endDate): void
    {
        $nextDueDate = $this->startDateInstance();

        while (true) {
            if ($nextDueDate->greaterThan($endDate)) {
                break;
            }

            if (! $this->occurrences()->whereDate('due_date', $nextDueDate->toDateString())->exists()) {
                $this->createOccurrence($nextDueDate);
            }

            $nextDueDate = $this->advanceDueDate($nextDueDate);
        }
    }

    private function markOverdueOccurrences(Carbon $today): void
    {
        $this->occurrences()
            ->whereDate('due_date', '<', $today->toDateString())
            ->where('status', InvoiceOccurrenceStatus::PENDING->value)
            ->update(['status' => InvoiceOccurrenceStatus::OVERDUE->value]);
    }

    private function createOccurrence(Carbon $dueDate): void
    {
        $this->occurrences()->create([
            'due_date' => $dueDate->toDateString(),
            'amount' => (float) $this->price,
            'currency' => $this->currency,
            'status' => InvoiceOccurrenceStatus::PENDING->value,
            'paid_at' => null,
        ]);
    }

    private function nextDueDate(): Carbon
    {
        $latestDueDate = $this->occurrences()->max('due_date');

        if ($latestDueDate !== null) {
            return $this->advanceDueDate(Carbon::parse($latestDueDate));
        }

        return $this->startDateInstance();
    }

    private function advanceDueDate(Carbon $dueDate): Carbon
    {
        return match ($this->recurrence) {
            InvoiceReccuranceType::WEEKLY->value => $dueDate->copy()->addWeek(),
            InvoiceReccuranceType::BIWEEKLY->value => $dueDate->copy()->addWeeks(2),
            InvoiceReccuranceType::QUARTERLY->value => $dueDate->copy()->addMonths(3),
            InvoiceReccuranceType::SEMIANNUAL->value => $dueDate->copy()->addMonths(6),
            InvoiceReccuranceType::YEARLY->value => $dueDate->copy()->addYear(),
            default => $dueDate->copy()->addMonth(),
        };
    }

    private function startDateInstance(): Carbon
    {
        return $this->start_date instanceof Carbon
            ? $this->start_date->copy()
            : Carbon::parse($this->start_date);
    }

    private function normalizedEndDate(): ?Carbon
    {
        if ($this->end_date === null) {
            return null;
        }

        return $this->end_date instanceof Carbon
            ? $this->end_date->copy()
            : Carbon::parse($this->end_date);
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'float',
    ];

    public function scopeVisible($query)
    {
        return $query->where('status', '!=', AgentTaskState::PROCESSING->value);
    }
}
