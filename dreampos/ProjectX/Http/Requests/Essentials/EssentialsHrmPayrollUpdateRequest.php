<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmPayrollUpdateRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() || $this->hasAnyPermission(['essentials.update_payroll']);
    }

    public function rules()
    {
        return [
            'staff_note' => ['nullable', 'string'],
        ];
    }
}
