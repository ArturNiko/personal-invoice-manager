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
        'price_total',
        'price_occurrence',
        'type',
        'created_at',
        'updated_at',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'type' => ['sometimes', Rule::enum(InvoiceType::class)],
            'recurrence' => ['sometimes', Rule::enum(InvoiceReccuranceType::class)],
            'min_occurrence' => ['sometimes', 'numeric', 'min:0'],
            'max_occurrence' => ['sometimes', 'numeric', 'gte:min_occurrence'],

            'sort' => ['sometimes', 'string', Rule::in(self::SORTABLE_FIELDS)],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}