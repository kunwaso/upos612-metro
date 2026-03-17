<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTrimSharePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'password' => 'required|string|max:255',
        ];
    }
}
