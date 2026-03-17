<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmLeaveUpdateRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.crud_all_leave', 'essentials.crud_own_leave']);
    }

    public function rules()
    {
        return [
            'essentials_leave_type_id' => ['sometimes', 'required', 'integer'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
            'reason' => ['nullable', 'string'],
            'employees' => ['nullable', 'array'],
            'employees.*' => ['integer'],
        ];
    }
}
