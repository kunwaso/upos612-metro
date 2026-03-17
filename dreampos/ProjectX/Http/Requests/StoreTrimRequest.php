<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Entities\Trim;

class StoreTrimRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.trim.create');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'name' => 'required|string|max:255',
            'part_number' => 'nullable|string|max:255',
            'trim_category_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_trim_categories', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'description' => 'nullable|string',
            'material' => 'nullable|string|max:255',
            'color_value' => 'nullable|string|max:255',
            'size_dimension' => 'nullable|string|max:255',
            'unit_of_measure' => ['required', 'string', 'max:50', Rule::in(Trim::UOM_OPTIONS)],
            'placement' => 'nullable|string|max:255',
            'quantity_per_garment' => 'nullable|numeric|min:0',
            'supplier_contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id)
                        ->whereIn('type', ['supplier', 'both']);
                }),
            ],
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:20',
            'lead_time_days' => 'nullable|integer|min:0',
            'care_testing' => 'nullable|string',
            'status' => ['required', 'string', Rule::in(Trim::STATUSES)],
            'qc_at' => 'nullable|date',
            'qc_notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_group' => 'nullable|string|max:255',
            'label_sub_type' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'button_ligne' => 'nullable|string|max:255',
            'button_holes' => 'nullable|string|max:255',
            'button_material' => 'nullable|string|max:255',
            'zipper_type' => 'nullable|string|max:255',
            'zipper_slider' => 'nullable|string|max:255',
            'interlining_type' => 'nullable|string|max:255',
            'quality_notes' => 'nullable|string',
            'shrinkage' => 'nullable|string|max:255',
            'rust_proof' => 'nullable|string|max:255',
            'comfort_notes' => 'nullable|string|max:255',
        ];
    }
}
