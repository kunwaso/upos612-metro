<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.assets.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'asset_account_id' => ['required', 'integer'],
            'accumulated_depreciation_account_id' => ['required', 'integer'],
            'depreciation_expense_account_id' => ['required', 'integer'],
            'default_useful_life_months' => ['required', 'integer', 'min:1'],
            'depreciation_method' => ['nullable', 'string', Rule::in(['straight_line'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
