<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportDatatableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.reports.view');
    }

    public function rules(): array
    {
        return [
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:500'],
            'search.value' => ['nullable', 'string', 'max:120'],
            'order' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'location_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
