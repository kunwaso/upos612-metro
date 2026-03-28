<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRepaymentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.loans.manage');
    }

    public function rules(): array
    {
        return [
            'loan_id' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'principal_due' => ['required', 'numeric', 'min:0'],
            'interest_due' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['planned', 'due', 'paid', 'overdue'])],
        ];
    }
}
