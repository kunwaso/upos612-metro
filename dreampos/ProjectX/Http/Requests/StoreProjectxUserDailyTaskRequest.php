<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectxUserDailyTaskRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'task_date' => 'nullable|date',
            'title' => 'required|string|max:191',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'title' => trim((string) $this->input('title', '')),
        ]);
    }
}
