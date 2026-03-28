<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.contracts.manage');
    }

    public function rules(): array
    {
        return [
            'contract_no' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:191'],
            'contact_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'business_location_id' => ['nullable', 'integer'],
            'signed_at' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'retention_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
        ];
    }
}
