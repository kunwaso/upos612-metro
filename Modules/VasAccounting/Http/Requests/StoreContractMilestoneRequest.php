<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.contracts.manage');
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'min:1'],
            'milestone_no' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:191'],
            'milestone_date' => ['nullable', 'date'],
            'billing_date' => ['nullable', 'date'],
            'revenue_amount' => ['required', 'numeric', 'min:0'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'retention_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'planned', 'posted', 'billed', 'collected'])],
        ];
    }
}
