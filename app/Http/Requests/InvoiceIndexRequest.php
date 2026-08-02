<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\InvoiceReccuranceType;



class InvoiceIndexRequest extends FormRequest
{
    private const SORTABLE_FIELDS = [
        'title',
        'status',
        'start_date',
        'end_date',
        'price',
        'type',
        'created_at',
        'updated_at',
    ];

    public function prepareForValidation(): void
    {
        if (!$this->filled('direction')) $this->merge(['direction' => 'asc']);
        if (!$this->filled('per_page')) $this->merge(['per_page' => 20]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'type' => ['sometimes', Rule::enum(InvoiceType::class)],
            'recurrence' => ['sometimes', Rule::enum(InvoiceReccuranceType::class)],
            'sort' => ['sometimes', 'string', Rule::in(self::SORTABLE_FIELDS)],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}