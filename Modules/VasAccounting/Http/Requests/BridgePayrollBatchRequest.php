<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BridgePayrollBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.payroll.manage');
    }

    public function rules(): array
    {
        return [
            'payroll_group_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
