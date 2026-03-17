<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DestroyFabricActivityLogRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check()
            && (auth()->user()->can('superadmin')
                || auth()->user()->can('projectx.fabric.activity.delete'));
    }

    public function rules()
    {
        $business_id = $this->session()->get('user.business_id');
        $fabric_id = (int) $this->route('fabric_id');

        return [
            'fabric_id' => [
                'required',
                'integer',
                Rule::exists('projectx_fabrics', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'log_id' => [
                'required',
                'integer',
                Rule::exists('projectx_fabric_activity_log', 'id')->where(function ($query) use ($business_id, $fabric_id) {
                    $query->where('business_id', $business_id)
                        ->where('fabric_id', $fabric_id);
                }),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'fabric_id' => $this->route('fabric_id'),
            'log_id' => $this->route('log_id'),
        ]);
    }
}
