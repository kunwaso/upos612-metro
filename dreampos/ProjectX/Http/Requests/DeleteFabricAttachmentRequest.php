<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteFabricAttachmentRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check()
            && (auth()->user()->can('projectx.fabric.create') || auth()->user()->can('product.create'));
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'fabric_id' => [
                'required',
                'integer',
                Rule::exists('projectx_fabrics', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'file_hash' => ['required', 'string', 'regex:/^[A-Fa-f0-9]{64}$/'],
            'redirect_to_product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'fabric_id' => $this->route('fabric_id'),
            'file_hash' => $this->route('file_hash'),
        ]);
    }
}
