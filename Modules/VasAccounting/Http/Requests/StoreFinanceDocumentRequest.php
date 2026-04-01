<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinanceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:60'],
            'document_no' => ['required', 'string', 'max:120'],
            'source_type' => ['nullable', 'string', 'max:60'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'counterparty_type' => ['nullable', 'string', 'max:30'],
            'counterparty_id' => ['nullable', 'integer', 'min:1'],
            'business_location_id' => ['nullable', 'integer', 'min:1'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'document_date' => ['required', 'date'],
            'posting_date' => ['nullable', 'date'],
            'gross_amount' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'net_amount' => ['nullable', 'numeric'],
            'open_amount' => ['nullable', 'numeric'],
            'meta' => ['nullable', 'array'],
            'links' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_no' => ['nullable', 'integer', 'min:1'],
            'lines.*.line_type' => ['nullable', 'string', 'max:40'],
            'lines.*.product_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.contact_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.business_location_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.account_hint_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.debit_account_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.credit_account_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_account_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.source_line_reference' => ['nullable', 'string', 'max:120'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['nullable', 'numeric'],
            'lines.*.unit_price' => ['nullable', 'numeric'],
            'lines.*.line_amount' => ['nullable', 'numeric'],
            'lines.*.tax_amount' => ['nullable', 'numeric'],
            'lines.*.gross_amount' => ['nullable', 'numeric'],
            'lines.*.dimensions' => ['nullable', 'array'],
            'lines.*.payload' => ['nullable', 'array'],
        ];
    }
}
