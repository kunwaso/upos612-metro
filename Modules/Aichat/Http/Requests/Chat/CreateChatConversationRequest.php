<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class CreateChatConversationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.edit');
    }

    public function rules()
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
