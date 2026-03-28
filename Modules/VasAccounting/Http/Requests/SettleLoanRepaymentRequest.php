<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettleLoanRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.loans.manage');
    }

    public function rules(): array
    {
        return [
            'settled_at' => ['nullable', 'date'],
        ];
    }
}
