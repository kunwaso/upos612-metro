<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class CancelChatActionRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.edit');
    }

    public function rules()
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}

