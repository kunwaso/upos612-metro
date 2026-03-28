<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisburseLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.loans.manage');
    }

    public function rules(): array
    {
        return [
            'disbursed_at' => ['nullable', 'date'],
        ];
    }
}
