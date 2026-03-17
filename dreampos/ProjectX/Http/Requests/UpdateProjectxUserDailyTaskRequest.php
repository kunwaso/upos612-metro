<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectxUserDailyTaskRequest extends FormRequest
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
            'title' => 'nullable|string|max:191',
            'task_date' => 'nullable|date',
            'is_completed' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:99999',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_completed' => $this->has('is_completed') ? $this->boolean('is_completed') : null,
            'title' => is_string($this->input('title')) ? trim((string) $this->input('title')) : $this->input('title'),
        ]);
    }
}
