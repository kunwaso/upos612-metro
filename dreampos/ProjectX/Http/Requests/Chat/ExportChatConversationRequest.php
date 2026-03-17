<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportChatConversationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.view');
    }

    public function rules()
    {
        return [
            'format' => ['nullable', 'string', Rule::in(['markdown', 'pdf'])],
        ];
    }
}

