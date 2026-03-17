<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Utils\ProductCostingUtil;

class UpdateProductQuoteRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('product_quote.edit');
    }

    protected function prepareForValidation(): void
    {
        $lines = (array) $this->input('lines', []);

        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $lineType = strtolower(trim((string) ($line['line_type'] ?? 'fabric')));
            if ($lineType === '') {
                $lineType = 'fabric';
            }

            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId <= 0) {
                $productId = (int) ($line['id'] ?? 0);
            }
            if ($productId <= 0 && $lineType === 'trim') {
                $productId = (int) ($line['trim_id'] ?? 0);
            }

            $line['line_type'] = $lineType;
            $line['product_id'] = $productId > 0 ? $productId : null;
            $lines[$index] = $line;
        }

        $this->merge(['lines' => $lines]);
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');
        $costingOptions = app(ProductCostingUtil::class)->getDropdownOptions($business_id);
        $allowedCurrencies = array_keys((array) ($costingOptions['currency'] ?? []));

        return [
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id)
                        ->whereIn('type', ['customer', 'both']);
                }),
            ],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'customer_email' => 'nullable|email|max:255',
            'customer_name' => 'nullable|string|max:255',
            'quote_date' => 'nullable|date',
            'expires_at' => 'required|date',
            'remark' => 'nullable|string|max:65535',
            'shipment_port' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.line_type' => ['nullable', 'string', Rule::in(['product', 'fabric', 'trim'])],
            'lines.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'lines.*.qty' => 'required|numeric|gt:0',
            'lines.*.purchase_uom' => 'nullable|string|max:20',
            'lines.*.base_mill_price' => 'nullable|numeric|min:0',
            'lines.*.test_cost' => 'nullable|numeric|min:0',
            'lines.*.surcharge' => 'nullable|numeric|min:0',
            'lines.*.finish_uplift_pct' => 'nullable|numeric|between:0,1',
            'lines.*.waste_pct' => 'nullable|numeric|between:0,1',
            'lines.*.currency' => ['required', 'string', 'max:20', Rule::in($allowedCurrencies)],
            'lines.*.incoterm' => 'nullable|string|max:50',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            if (empty($lines)) {
                return;
            }

            $business_id = (int) $this->session()->get('user.business_id');
            $costingOptions = app(ProductCostingUtil::class)->getDropdownOptions($business_id);
            $allowedIncoterms = array_values((array) ($costingOptions['incoterm'] ?? []));
            $shipmentPort = trim((string) ($this->input('shipment_port') ?? ''));
            $isLocalDelivery = $shipmentPort === '';

            $firstCurrency = (string) ($lines[0]['currency'] ?? '');
            $firstIncoterm = trim((string) ($lines[0]['incoterm'] ?? ''));

            foreach ($lines as $index => $line) {
                $hasProduct = (int) ($line['product_id'] ?? 0) > 0;
                $lineType = strtolower(trim((string) ($line['line_type'] ?? '')));
                if (! $hasProduct || ($lineType !== '' && ! in_array($lineType, ['product', 'fabric', 'trim'], true))) {
                    $validator->errors()->add('lines.' . $index . '.product_id', __('product.quote_line_type_invalid'));
                    continue;
                }

                $incoterm = trim((string) ($line['incoterm'] ?? ''));
                if (! $isLocalDelivery && $incoterm === '') {
                    $validator->errors()->add('lines.' . $index . '.incoterm', __('validation.required', ['attribute' => __('product.incoterm')]));
                    continue;
                }
                if ($incoterm !== '' && ! in_array($incoterm, $allowedIncoterms, true)) {
                    $validator->errors()->add('lines.' . $index . '.incoterm', __('product.quote_dropdown_invalid', ['field' => 'incoterm']));
                    continue;
                }

                if (
                    (string) ($line['currency'] ?? '') !== $firstCurrency
                    || $incoterm !== $firstIncoterm
                ) {
                    $validator->errors()->add('lines', __('product.quote_shared_currency_incoterm_required'));
                    break;
                }
            }
        });
    }
}
