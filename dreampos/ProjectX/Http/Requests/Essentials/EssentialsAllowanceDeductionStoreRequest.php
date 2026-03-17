<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsAllowanceDeductionStoreRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.add_allowance_and_deduction']);
    }

    public function rules()
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric'],
            'amount_type' => ['required', 'string', 'max:20'],
            'applicable_date' => ['nullable', 'date'],
            'employees' => ['nullable', 'array'],
            'employees.*' => ['integer'],
        ];
    }
}
