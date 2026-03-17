<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrimCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.trim.create');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('projectx_trim_categories', 'name')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('projectx::lang.trim_category_name_required'),
            'name.unique' => __('projectx::lang.trim_category_name_exists'),
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
        ]);
    }
}

