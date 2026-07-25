<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Enums\InvoiceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceReccuranceType;
use App\Enums\InvoiceCurrency;


class InvoicesRequest extends FormRequest
{

    public function prepareForValidation(): void
    {
        if (!$this->filled('status')) $this->merge(['status' => InvoiceStatus::PENDING->value]);

        if (!$this->filled('currency')) $this->merge(['currency' => config('invoices.default_currency', InvoiceCurrency::EUR->value)]);
        else $this->merge(['currency' => strtoupper((string) $this->input('currency'))]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required', 
                'string', 
                'max:255'
            ],
            'status' => [
                'required', 
                Rule::enum(InvoiceStatus::class)
            ],
            'start_date' => [
                'required', 
                'date'
            ],
            'end_date' => [
                Rule::excludeIf(fn () => $this->input('type') === InvoiceType::ONE_TIME->value),
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'currency' => [
                'required',
                'string', 
                'size:3', 
                Rule::enum(InvoiceCurrency::class)
            ],
            'type' => [
                'required', 
                Rule::enum(InvoiceType::class)
            ],
            'recurrence' => [
                Rule::requiredIf(fn () => $this->input('type') === InvoiceType::RECURRING->value),
                Rule::excludeIf(fn () => $this->input('type') === InvoiceType::ONE_TIME->value),
                'nullable',
                Rule::enum(InvoiceReccuranceType::class),
            ],
            'price_total' => [
                Rule::requiredIf(fn () => $this->input('type') === InvoiceType::ONE_TIME->value),
                Rule::excludeIf(fn () => $this->input('type') === InvoiceType::RECURRING->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'price_occurrence' => [
                Rule::requiredIf(fn () => $this->input('type') === InvoiceType::RECURRING->value),
                Rule::excludeIf(fn () => $this->input('type') === InvoiceType::ONE_TIME->value),
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.enum' => "The type must be either 'one-time' or 'recurring'.",
            'recurrence.required' => 'The recurrence field is required for recurring invoices.',
            'recurrence.enum' => 'The recurrence must be one of: weekly, biweekly, monthly, quarterly, semiannual, yearly.',
            'end_date.required' => 'The end date is required for recurring invoices.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'price_total.required' => 'The total price is required for one-time invoices.',
            'price_occurrence.required' => 'The occurrence price is required for recurring invoices.',
            'currency.in' => 'The selected currency is not supported.',
        ];
    }
}
