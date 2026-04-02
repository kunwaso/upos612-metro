<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardUiDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.access');
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer', 'min:1'],
            'range' => ['nullable', 'in:month,quarter,year'],
        ];
    }
}
