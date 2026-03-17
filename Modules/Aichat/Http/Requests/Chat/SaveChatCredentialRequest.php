<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveChatCredentialRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.view');
    }

    public function rules()
    {
        return [
            'scope' => ['required', 'string', Rule::in(['user', 'business'])],
            'provider' => ['required', 'string', Rule::in(array_keys((array) config('aichat.chat.providers', [])))],
            'api_key' => ['required', 'string', 'min:8', 'max:4096'],
        ];
    }
}


