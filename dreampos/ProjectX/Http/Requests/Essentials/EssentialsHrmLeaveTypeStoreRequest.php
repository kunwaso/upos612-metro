<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmLeaveTypeStoreRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.crud_leave_type']);
    }

    public function rules()
    {
        return [
            'leave_type' => ['required', 'string', 'max:255'],
            'max_leave_count' => ['nullable', 'numeric', 'min:0'],
            'leave_count_interval' => ['nullable', 'string', 'max:50'],
        ];
    }
}
