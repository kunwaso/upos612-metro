<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmClockInOutRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess();
    }

    public function rules()
    {
        return [
            'type' => ['required', Rule::in(['clock_in', 'clock_out'])],
            'clock_in_note' => ['nullable', 'string'],
            'clock_out_note' => ['nullable', 'string'],
            'clock_in_out_location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
