<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmPayrollGroupUpdateRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() || $this->hasAnyPermission(['essentials.update_payroll']);
    }

    public function rules()
    {
        return [
            'payroll_group_id' => ['required', 'integer'],
            'transaction_date' => ['required', 'date'],
            'payrolls' => ['required', 'array', 'min:1'],
            'payroll_group_name' => ['nullable', 'string', 'max:255'],
            'payroll_group_status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
