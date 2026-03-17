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
        $allowedIncoterms = array_values((array) ($costingOptions['incoterm'] ?? []));
        $allowedPurchaseUom = array_values((array) ($costingOptions['purchase_uom'] ?? []));

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
            'qty' => 'required|numeric|gt:0',
            'purchase_uom' => ['required', 'string', 'max:20', Rule::in($allowedPurchaseUom)],
            'base_mill_price' => 'nullable|numeric|min:0',
            'test_cost' => 'nullable|numeric|min:0',
            'surcharge' => 'nullable|numeric|min:0',
            'finish_uplift_pct' => 'nullable|numeric|between:0,1',
            'waste_pct' => 'nullable|numeric|between:0,1',
            'currency' => ['required', 'string', 'max:20', Rule::in($allowedCurrencies)],
            'incoterm' => ['required', 'string', 'max:50', Rule::in($allowedIncoterms)],
        ];
    }
}
