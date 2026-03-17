<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateChatConversationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.edit');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'fabric_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_fabrics', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }
}
