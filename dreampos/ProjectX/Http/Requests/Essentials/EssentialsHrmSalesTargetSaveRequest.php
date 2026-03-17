<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmSalesTargetSaveRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.access_sales_target']);
    }

    public function rules()
    {
        return [
            'user_id' => ['required', 'integer'],
            'sales_amount_start' => ['nullable', 'array'],
            'sales_amount_end' => ['nullable', 'array'],
            'commission' => ['nullable', 'array'],
            'edit_target' => ['nullable', 'array'],
        ];
    }
}
