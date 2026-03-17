<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Entities\Fabric;

class StoreFabricRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('projectx.fabric.create');
    }

    public function rules()
    {
        $business_id = request()->session()->get('user.business_id');

        return [
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:' . implode(',', Fabric::STATUSES),
            'fiber' => 'nullable|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'supplier_contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('business_id', $business_id),
            ],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'due_date' => 'nullable|date',
            'progress_percent' => 'nullable|integer|min:0|max:100',
        ];
    }
}
