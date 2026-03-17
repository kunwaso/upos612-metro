<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveChatMessageFeedbackRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.edit');
    }

    public function rules()
    {
        return [
            'feedback' => ['required', 'string', Rule::in(['up', 'down'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}


