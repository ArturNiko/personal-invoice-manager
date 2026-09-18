<?php

namespace App\Models;

use App\Enums\InvoiceOccurrenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property Carbon $due_date
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property Carbon|null $paid_at
 */
class InvoiceOccurrence extends Model
{
    protected $fillable = [
        'invoice_id',
        'due_date',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function markAsPaid(): void
    {
        $this->status = InvoiceOccurrenceStatus::PAID->value;
        $this->paid_at = now();
        $this->save();
    }
}
