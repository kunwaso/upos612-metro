<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatMemoryFactRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.settings');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        $memoryId = (int) $this->route('memory');

        return [
            'memory' => ['required', 'integer', 'min:1'],
            'memory_key' => [
                'required',
                'string',
                'max:150',
                Rule::unique('projectx_chat_memory', 'memory_key')
                    ->ignore($memoryId)
                    ->where(function ($query) use ($business_id, $user_id) {
                        return $query
                            ->where('business_id', $business_id)
                            ->where('user_id', $user_id);
                    }),
            ],
            'memory_value' => ['required', 'string', 'max:20000'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'memory' => (int) $this->route('memory'),
            'memory_key' => trim((string) $this->input('memory_key')),
            'memory_value' => trim((string) $this->input('memory_value')),
        ]);
    }
}
