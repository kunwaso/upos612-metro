<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.costing.manage');
    }

    public function rules(): array
    {
        return [
            'project_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:191'],
            'contact_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'on_hold', 'completed', 'cancelled'])],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
