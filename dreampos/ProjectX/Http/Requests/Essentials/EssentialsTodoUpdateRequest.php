<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsTodoUpdateRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.edit_todos']);
    }

    public function rules()
    {
        $business_id = $this->businessId();

        return [
            'only_status' => ['nullable', 'boolean'],
            'task' => ['required_without:only_status', 'string', 'max:255'],
            'date' => ['required_without:only_status', 'date'],
            'description' => ['nullable', 'string'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['required', Rule::in(['new', 'in_progress', 'on_hold', 'completed'])],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'users' => ['nullable', 'array'],
            'users.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $date = $this->input('date');
        if (empty($date) && ! empty($this->input('start_date'))) {
            $date = $this->input('start_date');
        }

        $this->merge([
            'date' => $date,
            'only_status' => filter_var($this->input('only_status'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
