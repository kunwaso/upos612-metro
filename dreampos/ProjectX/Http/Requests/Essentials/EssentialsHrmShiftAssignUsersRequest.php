<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmShiftAssignUsersRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccessOrAdmin();
    }

    public function rules()
    {
        return [
            'shift_id' => ['required', 'integer'],
            'user_shift' => ['nullable', 'array'],
        ];
    }
}
