<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.tools.manage');
    }

    public function rules(): array
    {
        return [
            'tool_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:191'],
            'business_location_id' => ['nullable', 'integer'],
            'expense_account_id' => ['required', 'integer'],
            'asset_account_id' => ['required', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'original_cost' => ['required', 'numeric', 'min:0'],
            'remaining_value' => ['nullable', 'numeric', 'min:0'],
            'amortization_months' => ['required', 'integer', 'min:1'],
            'start_amortization_at' => ['required', 'date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'issued', 'fully_amortized', 'retired'])],
        ];
    }
}
