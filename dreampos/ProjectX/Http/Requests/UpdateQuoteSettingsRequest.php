<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuoteSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.quote.edit');
    }

    public function rules()
    {
        return [
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'default_currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')],
            'incoterm_options' => ['nullable', 'array'],
            'incoterm_options.*' => ['nullable', 'string', 'max:50'],
            'purchase_uom_options' => ['nullable', 'array'],
            'purchase_uom_options.*' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'default_currency_id' => $this->input('default_currency_id') !== '' ? $this->input('default_currency_id') : null,
            'incoterm_options' => $this->normalizeOptions((array) $this->input('incoterm_options', [])),
            'purchase_uom_options' => $this->normalizeOptions((array) $this->input('purchase_uom_options', [])),
        ]);
    }

    /**
     * @param  array<int, mixed>  $options
     * @return array<int, string>
     */
    protected function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $option) {
            $value = trim((string) $option);
            if ($value === '') {
                continue;
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }
}

