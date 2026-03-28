<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.budgets.manage');
    }

    public function rules(): array
    {
        return [
            'budget_id' => ['required', 'integer', 'min:1'],
            'account_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'budget_amount' => ['required', 'numeric', 'min:0'],
            'committed_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
