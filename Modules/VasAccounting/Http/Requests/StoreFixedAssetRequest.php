<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.assets.manage');
    }

    public function rules(): array
    {
        return [
            'asset_category_id' => ['required', 'integer'],
            'asset_code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'acquisition_date' => ['required', 'date'],
            'capitalization_date' => ['required', 'date'],
            'vendor_contact_id' => ['nullable', 'integer'],
            'business_location_id' => ['nullable', 'integer'],
            'original_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['nullable', 'integer', 'min:1'],
            'asset_account_id' => ['nullable', 'integer'],
            'accumulated_depreciation_account_id' => ['nullable', 'integer'],
            'depreciation_expense_account_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
