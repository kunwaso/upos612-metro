<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsHrmAttendanceUpdateRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccessOrAdmin();
    }

    public function rules()
    {
        return [
            'clock_in_time' => ['required', 'date'],
            'clock_out_time' => ['nullable', 'date'],
            'clock_in_note' => ['nullable', 'string'],
            'clock_out_note' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
