<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ListChatConversationsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.view');
    }

    public function rules()
    {
        return [
            'include_archived' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'include_archived' => $this->boolean('include_archived'),
        ]);
    }
}
