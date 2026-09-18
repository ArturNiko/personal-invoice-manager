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
            $invoice->generateOccurrencesIfNeeded();
        });

        static::updated(function (self $invoice): void {
            if ($invoice->type !== InvoiceType::RECURRING->value) {
                return;
            }

            if ($invoice->wasChanged(['type', 'recurrence', 'start_date', 'price', 'currency']) && $invoice->occurrences()->doesntExist()) {
                $invoice->generateOccurrencesIfNeeded();
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
        if ($this->type !== InvoiceType::RECURRING->value || $this->occurrences()->exists()) {
            return;
        }

        $startDate = $this->start_date instanceof Carbon
            ? $this->start_date->copy()
            : Carbon::parse($this->start_date);

        foreach (range(0, 5) as $index) {
            $dueDate = match ($this->recurrence) {
                InvoiceReccuranceType::WEEKLY->value => $startDate->copy()->addWeeks($index),
                InvoiceReccuranceType::BIWEEKLY->value => $startDate->copy()->addWeeks($index * 2),
                InvoiceReccuranceType::QUARTERLY->value => $startDate->copy()->addMonths($index * 3),
                InvoiceReccuranceType::SEMIANNUAL->value => $startDate->copy()->addMonths($index * 6),
                InvoiceReccuranceType::YEARLY->value => $startDate->copy()->addYears($index),
                default => $startDate->copy()->addMonths($index),
            };

            $this->occurrences()->create([
                'due_date' => $dueDate->toDateString(),
                'amount' => (float) $this->price,
                'currency' => $this->currency,
                'status' => InvoiceOccurrenceStatus::PENDING->value,
                'paid_at' => null,
            ]);
        }
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
