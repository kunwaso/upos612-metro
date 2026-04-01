<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_id' => ['nullable', 'integer', 'min:1'],
            'document_family' => ['required_without:document_id', 'string', 'max:40'],
            'document_type' => ['required_without:document_id', 'string', 'max:60'],
            'document_no' => ['nullable', 'string', 'max:120'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'document_date' => ['required_without:document_id', 'date'],
            'posting_date' => ['nullable', 'date'],
            'business_location_id' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['required', 'string', 'max:40'],
            'lines' => ['required_without:document_id', 'array', 'min:1'],
            'lines.*.line_no' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.line_amount' => ['nullable', 'numeric'],
            'lines.*.tax_amount' => ['nullable', 'numeric'],
            'lines.*.gross_amount' => ['nullable', 'numeric'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.debit_account_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.credit_account_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_account_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.dimensions' => ['nullable', 'array'],
            'lines.*.payload' => ['nullable', 'array'],
        ];
    }
}
