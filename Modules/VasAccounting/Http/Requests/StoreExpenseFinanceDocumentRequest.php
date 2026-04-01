<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseFinanceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(['expense_claim', 'advance_request', 'advance_settlement', 'reimbursement_voucher'])],
            'document_no' => ['required', 'string', 'max:120'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'claimant_user_id' => ['nullable', 'integer', 'min:1'],
            'advance_request_id' => ['nullable', 'integer', 'min:1'],
            'expense_claim_id' => ['nullable', 'integer', 'min:1'],
            'business_location_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'cost_center_id' => ['nullable', 'integer', 'min:1'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'tax_code_id' => ['nullable', 'integer', 'min:1'],
            'document_date' => ['required', 'date'],
            'posting_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'debit_account_id' => ['required', 'integer', 'min:1'],
            'credit_account_id' => ['required', 'integer', 'min:1', 'different:debit_account_id'],
            'tax_account_id' => ['nullable', 'integer', 'min:1'],
            'tax_entry_side' => ['nullable', 'string', Rule::in(['debit', 'credit'])],
        ];
    }
}
