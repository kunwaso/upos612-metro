<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.inventory.manage');
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(['receipt', 'issue', 'transfer', 'adjustment'])],
            'document_date' => ['required', 'date'],
            'posting_date' => ['required', 'date'],
            'business_location_id' => ['nullable', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'destination_warehouse_id' => ['nullable', 'integer', 'different:warehouse_id', Rule::requiredIf(fn () => $this->input('document_type') === 'transfer')],
            'offset_account_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:120'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'pending_approval', 'approved'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.variation_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'lines.*.amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.direction' => ['nullable', 'string', Rule::in(['increase', 'decrease']), Rule::requiredIf(fn () => $this->input('document_type') === 'adjustment')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'posting_date' => $this->input('posting_date') ?: $this->input('document_date'),
        ]);
    }
}
