<?php

namespace App\Models;

use App\Enums\AgentTaskState;
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

    public function user()
    {
        return $this->belongsTo(User::class);
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
