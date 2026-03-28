<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.loans.manage');
    }

    public function rules(): array
    {
        return [
            'loan_no' => ['required', 'string', 'max:80'],
            'lender_name' => ['required', 'string', 'max:191'],
            'bank_account_id' => ['nullable', 'integer'],
            'contract_id' => ['nullable', 'integer'],
            'principal_amount' => ['required', 'numeric', 'min:0.0001'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'disbursement_date' => ['nullable', 'date'],
            'maturity_date' => ['nullable', 'date', 'after_or_equal:disbursement_date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'settled', 'closed'])],
        ];
    }
}
