<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmChatActionRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.edit');
    }

    public function rules()
    {
        return [
            'confirm_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}

