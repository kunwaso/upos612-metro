<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsTodoStoreRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.add_todos']);
    }

    public function rules()
    {
        $business_id = $this->businessId();

        return [
            'task' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['nullable', Rule::in(['new', 'in_progress', 'on_hold', 'completed'])],
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
        ]);
    }
}
