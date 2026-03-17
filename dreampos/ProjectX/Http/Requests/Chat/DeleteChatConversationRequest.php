<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class DeleteChatConversationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.edit');
    }

    public function rules()
    {
        return [
            'id' => ['required', 'uuid'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'id' => (string) $this->route('id'),
        ]);
    }
}
