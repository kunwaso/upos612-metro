<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmLeaveStatusRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.approve_leave']);
    }

    public function rules()
    {
        return [
            'leave_id' => ['required', 'integer'],
            'status' => ['required', Rule::in(['pending', 'approved', 'cancelled'])],
            'status_note' => ['nullable', 'string'],
            'is_additional' => ['nullable', 'boolean'],
        ];
    }
}
