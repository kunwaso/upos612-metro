<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsSettingsUpdateRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['edit_essentials_settings']);
    }

    public function rules()
    {
        return [
            'leave_ref_no_prefix' => ['nullable', 'string', 'max:40'],
            'leave_instructions' => ['nullable', 'string'],
            'payroll_ref_no_prefix' => ['nullable', 'string', 'max:40'],
            'essentials_todos_prefix' => ['nullable', 'string', 'max:40'],
            'grace_before_checkin' => ['nullable', 'numeric', 'min:0'],
            'grace_after_checkin' => ['nullable', 'numeric', 'min:0'],
            'grace_before_checkout' => ['nullable', 'numeric', 'min:0'],
            'grace_after_checkout' => ['nullable', 'numeric', 'min:0'],
            'is_location_required' => ['nullable', 'boolean'],
            'calculate_sales_target_commission_without_tax' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_location_required' => filter_var($this->input('is_location_required'), FILTER_VALIDATE_BOOLEAN),
            'calculate_sales_target_commission_without_tax' => filter_var($this->input('calculate_sales_target_commission_without_tax'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
