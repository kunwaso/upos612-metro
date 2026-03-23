<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrepareChatActionRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.edit');
    }

    public function rules()
    {
        return [
            'module' => ['required', 'string', Rule::in(['products', 'contacts', 'settings', 'sales', 'quotes', 'purchases', 'reports'])],
            'action' => ['required', 'string', 'max:50'],
            'payload' => ['nullable', 'array'],
            'channel' => ['nullable', 'string', Rule::in(['web', 'telegram'])],
        ];
    }
}

