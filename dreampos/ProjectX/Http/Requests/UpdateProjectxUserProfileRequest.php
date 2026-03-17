<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectxUserProfileRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');
        $target_user_id = (int) ($this->input('user_id') ?: $this->session()->get('user.id'));

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'surname' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target_user_id)->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'language' => 'nullable|string|max:20',
            'marital_status' => ['nullable', Rule::in(['married', 'unmarried', 'divorced'])],
            'blood_group' => 'nullable|string|max:20',
            'contact_number' => 'nullable|string|max:30',
            'fb_link' => 'nullable|string|max:255',
            'twitter_link' => 'nullable|string|max:255',
            'social_media_1' => 'nullable|string|max:255',
            'social_media_2' => 'nullable|string|max:255',
            'permanent_address' => 'nullable|string|max:1000',
            'current_address' => 'nullable|string|max:1000',
            'guardian_name' => 'nullable|string|max:191',
            'custom_field_1' => 'nullable|string|max:191',
            'custom_field_2' => 'nullable|string|max:191',
            'custom_field_3' => 'nullable|string|max:191',
            'custom_field_4' => 'nullable|string|max:191',
            'id_proof_name' => 'nullable|string|max:191',
            'id_proof_number' => 'nullable|string|max:191',
            'gender' => ['nullable', Rule::in(['male', 'female', 'others'])],
            'family_number' => 'nullable|string|max:30',
            'alt_number' => 'nullable|string|max:30',
            'dob' => 'nullable|date',
            'bank_details' => 'nullable|array',
            'bank_details.*' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
