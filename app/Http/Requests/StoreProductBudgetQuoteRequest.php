<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Utils\ProductCostingUtil;

class StoreProductBudgetQuoteRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('product_quote.create');
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
            'shipment_port' => 'nullable|string|max:255',
            'qty' => 'required|numeric|gt:0',
            'purchase_uom' => 'nullable|string|max:20',
            'base_mill_price' => 'nullable|numeric|min:0',
            'test_cost' => 'nullable|numeric|min:0',
            'surcharge' => 'nullable|numeric|min:0',
            'finish_uplift_pct' => 'nullable|numeric|between:0,1',
            'waste_pct' => 'nullable|numeric|between:0,1',
            'currency' => ['required', 'string', 'max:20', Rule::in($allowedCurrencies)],
            'incoterm' => 'nullable|string|max:50',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $business_id = (int) $this->session()->get('user.business_id');
            $costingOptions = app(ProductCostingUtil::class)->getDropdownOptions($business_id);
            $allowedIncoterms = array_values((array) ($costingOptions['incoterm'] ?? []));
            $incoterm = trim((string) ($this->input('incoterm') ?? ''));
            $shipmentPort = trim((string) ($this->input('shipment_port') ?? ''));
            $isLocalDelivery = $shipmentPort === '';

            if (! $isLocalDelivery && $incoterm === '') {
                $validator->errors()->add('incoterm', __('validation.required', ['attribute' => __('product.incoterm')]));
                return;
            }

            if ($incoterm !== '' && ! in_array($incoterm, $allowedIncoterms, true)) {
                $validator->errors()->add('incoterm', __('product.quote_dropdown_invalid', ['field' => 'incoterm']));
            }
        });
    }
}
