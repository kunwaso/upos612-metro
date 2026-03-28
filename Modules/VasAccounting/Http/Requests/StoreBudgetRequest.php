<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.budgets.manage');
    }

    public function rules(): array
    {
        return [
            'budget_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:191'],
            'department_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'revised', 'closed'])],
        ];
    }
}
