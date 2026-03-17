<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Entities\Trim;
use Modules\ProjectX\Utils\FabricCostingUtil;

class UpdateQuoteRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.quote.edit');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');
        $costingOptions = app(FabricCostingUtil::class)->getDropdownOptions($business_id);
        $allowedCurrencies = array_keys((array) ($costingOptions['currency'] ?? []));
        $allowedIncoterms = array_values((array) ($costingOptions['incoterm'] ?? []));
        $allowedPurchaseUom = array_values(array_unique(array_filter(array_merge(
            array_values((array) ($costingOptions['purchase_uom'] ?? [])),
            Trim::UOM_OPTIONS
        ))));

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
            'lines.*.line_type' => ['nullable', 'string', Rule::in(['fabric', 'trim'])],
            'lines.*.fabric_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_fabrics', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'lines.*.trim_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_trims', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'lines.*.qty' => 'required|numeric|gt:0',
            'lines.*.purchase_uom' => ['required', 'string', 'max:20', Rule::in($allowedPurchaseUom)],
            'lines.*.base_mill_price' => 'nullable|numeric|min:0',
            'lines.*.test_cost' => 'nullable|numeric|min:0',
            'lines.*.surcharge' => 'nullable|numeric|min:0',
            'lines.*.finish_uplift_pct' => 'nullable|numeric|between:0,1',
            'lines.*.waste_pct' => 'nullable|numeric|between:0,1',
            'lines.*.currency' => ['required', 'string', 'max:20', Rule::in($allowedCurrencies)],
            'lines.*.incoterm' => ['required', 'string', 'max:50', Rule::in($allowedIncoterms)],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            if (empty($lines)) {
                return;
            }

            $firstCurrency = (string) ($lines[0]['currency'] ?? '');
            $firstIncoterm = (string) ($lines[0]['incoterm'] ?? '');

            foreach ($lines as $index => $line) {
                $hasFabric = (int) ($line['fabric_id'] ?? 0) > 0;
                $hasTrim = (int) ($line['trim_id'] ?? 0) > 0;

                if ($hasFabric === $hasTrim) {
                    $validator->errors()->add('lines.' . $index . '.line_type', __('projectx::lang.quote_line_type_invalid'));

                    continue;
                }

                $lineType = strtolower(trim((string) ($line['line_type'] ?? '')));
                if ($lineType === 'fabric' && ! $hasFabric) {
                    $validator->errors()->add('lines.' . $index . '.fabric_id', __('projectx::lang.quote_line_type_invalid'));
                }
                if ($lineType === 'trim' && ! $hasTrim) {
                    $validator->errors()->add('lines.' . $index . '.trim_id', __('projectx::lang.quote_line_type_invalid'));
                }

                if (
                    (string) ($line['currency'] ?? '') !== $firstCurrency
                    || (string) ($line['incoterm'] ?? '') !== $firstIncoterm
                ) {
                    $validator->errors()->add('lines', __('projectx::lang.quote_shared_currency_incoterm_required'));
                    break;
                }
            }
        });
    }
}
