<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Entities\UserAttendanceOverride;

class UpsertProjectxAttendanceOverrideRequest extends FormRequest
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
            'work_date' => 'required|date',
            'hour_slot' => 'required|integer|min:0|max:23',
            'status' => ['required', 'string', Rule::in(UserAttendanceOverride::STATUSES)],
            'note' => 'nullable|string|max:1000',
        ];
    }
}
