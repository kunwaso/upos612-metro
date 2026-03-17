<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreTelegramBotRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.settings');
    }

    public function rules()
    {
        return [
            'bot_token' => ['required', 'string', 'min:10', 'max:4096'],
        ];
    }

    public function messages()
    {
        return [
            'bot_token.required' => __('aichat::lang.telegram_bot_token_required'),
        ];
    }
}

