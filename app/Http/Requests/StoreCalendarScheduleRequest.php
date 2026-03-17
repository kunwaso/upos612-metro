<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarScheduleRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'all_day' => $this->boolean('all_day'),
            'user_id' => $this->filled('user_id') ? (int) $this->input('user_id') : null,
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
        ]);
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'all_day' => ['required', 'boolean'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[A-Fa-f0-9]{6}$/'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('business_locations', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }
}
