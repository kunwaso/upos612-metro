<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ShareChatConversationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.view');
    }

    public function rules()
    {
        return [
            'ttl_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
        ];
    }
}

