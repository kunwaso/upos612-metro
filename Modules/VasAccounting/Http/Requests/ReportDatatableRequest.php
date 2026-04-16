<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportDatatableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.reports.view');
    }

    public function rules(): array
    {
        $rules = [
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:500'],
            'search.value' => ['nullable', 'string', 'max:120'],
            'order' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'location_id' => ['nullable', 'integer', 'min:1'],
        ];

        if ($this->isFinancialStatementContext()) {
            $rules = array_replace($rules, $this->financialStatementRules());
        }

        return $rules;
    }

    public function financialStatementRules(): array
    {
        return [
            'statement' => ['nullable', 'string', Rule::in(array_keys((array) config('vasaccounting.financial_statement_types', [])))],
            'period_id' => ['nullable', 'integer', 'min:1'],
            'comparative_period_id' => ['nullable', 'integer', 'min:1'],
            'format' => ['nullable', 'string', Rule::in(['html', 'pdf', 'xlsx'])],
            'standard_profile' => ['nullable', 'string'],
        ];
    }

    protected function isFinancialStatementContext(): bool
    {
        return (string) $this->route('reportKey') === 'financial_statements'
            || str_contains((string) optional($this->route())->getName(), 'financial_statements')
            || $this->filled('statement')
            || $this->filled('period_id')
            || $this->filled('comparative_period_id');
    }
}
