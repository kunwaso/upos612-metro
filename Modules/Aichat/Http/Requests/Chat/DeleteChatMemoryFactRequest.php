<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class DeleteChatMemoryFactRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.settings');
    }

    public function rules()
    {
        return [
            'memory' => ['required', 'integer', 'min:1'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'memory' => (int) $this->route('memory'),
        ]);
    }
}


