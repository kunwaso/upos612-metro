<?php

namespace Modules\Projectauto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectautoRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('projectauto.settings.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'trigger_type' => ['required', 'in:payment_status_updated,sales_order_status_updated'],
            'task_type' => ['required', 'in:create_invoice,add_product,adjust_stock'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'stop_on_match' => ['nullable', 'boolean'],
            'conditions' => ['nullable', 'string'],
            'payload_template' => ['nullable', 'string'],
        ];
    }
}
