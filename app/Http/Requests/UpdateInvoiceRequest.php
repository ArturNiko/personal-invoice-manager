<?php

namespace App\Http\Requests;

use App\Enums\InvoiceType;
use App\Models\Invoice;

class UpdateInvoiceRequest extends InvoicesRequest
{
    public function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->request->remove('type');
        $this->request->remove('recurrence');

        $invoice = $this->route('invoice');

        if ($invoice instanceof Invoice && $invoice->type === InvoiceType::RECURRING->value) {
            $this->request->remove('start_date');
        }
    }

    public function rules(): array
    {
        return $this->commonRules();
    }
}
