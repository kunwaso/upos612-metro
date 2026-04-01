<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreasuryFinanceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(['cash_transfer', 'bank_transfer', 'petty_cash_expense'])],
            'document_no' => ['required', 'string', 'max:120'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'business_location_id' => ['nullable', 'integer', 'min:1'],
            'document_date' => ['required', 'date'],
            'posting_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'source_account_id' => ['required', 'integer', 'min:1'],
            'target_account_id' => ['required', 'integer', 'min:1', 'different:source_account_id'],
        ];
    }
}
