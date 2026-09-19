<?php

namespace App\Http\Requests;

use App\Enums\InvoiceCurrency;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends InvoicesRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'type' => [
                'required',
                Rule::enum(InvoiceType::class),
            ],
            'recurrence' => [
                Rule::requiredIf(fn () => $this->input('type') === InvoiceType::RECURRING->value),
                'nullable',
                Rule::enum(InvoiceReccuranceType::class),
            ],
        ]);
    }
}
