<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankStatementLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.cash_bank.manage');
    }

    public function rules(): array
    {
        return [
            'match_status' => ['required', 'string', Rule::in(['matched', 'ignored', 'unmatched'])],
            'matched_voucher_id' => ['nullable', 'integer', 'required_if:match_status,matched'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
