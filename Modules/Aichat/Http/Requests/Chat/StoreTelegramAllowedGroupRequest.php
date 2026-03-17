<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreTelegramAllowedGroupRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.settings');
    }

    public function rules()
    {
        return [
            'telegram_chat_id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}

