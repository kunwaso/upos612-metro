<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmPayrollAddPaymentRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccessOrAdmin();
    }

    public function rules()
    {
        return [
            'payroll_group_id' => ['required', 'integer'],
            'payments' => ['required', 'array'],
        ];
    }
}
