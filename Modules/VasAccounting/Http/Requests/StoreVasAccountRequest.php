<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVasAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.chart.manage');
    }

    public function rules(): array
    {
        return [
            'account_code' => ['required', 'string', 'max:30'],
            'account_name' => ['required', 'string', 'max:191'],
            'account_type' => ['required', 'string', 'max:50'],
            'account_category' => ['nullable', 'string', 'max:100'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'parent_id' => ['nullable', 'integer'],
        ];
    }
}
