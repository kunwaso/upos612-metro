<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveChatMessageFeedbackRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.edit');
    }

    public function rules()
    {
        return [
            'feedback' => ['required', 'string', Rule::in(['up', 'down'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

