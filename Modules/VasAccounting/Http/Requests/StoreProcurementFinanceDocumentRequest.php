<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementFinanceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(['purchase_requisition', 'purchase_order', 'goods_receipt', 'supplier_invoice'])],
            'document_no' => ['required', 'string', 'max:120'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'parent_document_id' => ['nullable', 'integer', 'min:1'],
            'counterparty_id' => ['nullable', 'integer', 'min:1'],
            'business_location_id' => ['nullable', 'integer', 'min:1'],
            'document_date' => ['required', 'date'],
            'posting_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'tax_code_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'debit_account_id' => ['nullable', 'integer', 'min:1'],
            'credit_account_id' => ['nullable', 'integer', 'min:1', 'different:debit_account_id'],
            'tax_account_id' => ['nullable', 'integer', 'min:1'],
            'tax_entry_side' => ['nullable', 'string', Rule::in(['debit', 'credit'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $documentType = (string) $this->input('document_type');

            if (in_array($documentType, ['purchase_order', 'goods_receipt', 'supplier_invoice'], true) && ! $this->filled('counterparty_id')) {
                $validator->errors()->add('counterparty_id', 'A supplier is required for this procurement document type.');
            }

            if (in_array($documentType, ['goods_receipt', 'supplier_invoice'], true)) {
                if (! $this->filled('debit_account_id')) {
                    $validator->errors()->add('debit_account_id', 'A debit account is required for posting-enabled procurement documents.');
                }

                if (! $this->filled('credit_account_id')) {
                    $validator->errors()->add('credit_account_id', 'A credit account is required for posting-enabled procurement documents.');
                }
            }
        });
    }
}
