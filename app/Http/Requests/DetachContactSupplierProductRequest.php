<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetachContactSupplierProductRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('supplier.update');
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'contact_id' => $this->route('contact'),
            'product_id' => $this->route('product'),
        ]);
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id)
                        ->whereIn('type', ['supplier', 'both']);
                }),
            ],
            'product_id' => ['required', 'integer'],
        ];
    }
}
