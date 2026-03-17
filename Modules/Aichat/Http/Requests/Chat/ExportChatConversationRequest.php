<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportChatConversationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.view');
    }

    public function rules()
    {
        return [
            'format' => ['nullable', 'string', Rule::in(['markdown', 'pdf'])],
        ];
    }
}


