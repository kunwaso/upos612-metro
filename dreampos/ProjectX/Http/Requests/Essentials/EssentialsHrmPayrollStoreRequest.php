<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmPayrollStoreRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() || $this->hasAnyPermission(['essentials.create_payroll']);
    }

    public function rules()
    {
        return [
            'transaction_date' => ['required', 'date'],
            'payrolls' => ['required', 'array', 'min:1'],
            'payroll_group_name' => ['nullable', 'string', 'max:255'],
            'payroll_group_status' => ['nullable', 'string', 'max:50'],
            'location_id' => ['nullable', 'integer'],
        ];
    }
}
