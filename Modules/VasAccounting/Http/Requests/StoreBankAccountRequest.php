<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.cash_bank.manage');
    }

    public function rules(): array
    {
        return [
            'account_code' => ['required', 'string', 'max:80'],
            'bank_name' => ['required', 'string', 'max:191'],
            'account_name' => ['required', 'string', 'max:191'],
            'account_number' => ['required', 'string', 'max:120'],
            'business_location_id' => ['nullable', 'integer'],
            'ledger_account_id' => ['nullable', 'integer'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
