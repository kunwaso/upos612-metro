<?php

namespace Modules\ProjectX\Http\Requests;

use App\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Entities\Fabric;

class UpdateFabricSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('projectx.fabric.create')
            || auth()->user()->can('product.create');
    }

    public function rules()
    {
        $business_id = request()->session()->get('user.business_id');

        return [
            'name' => 'required|string|max:255',
            'status' => ['required', 'string', Rule::in(Fabric::STATUSES)],
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',

            'supplier_contact_ids' => 'nullable|array',
            'supplier_contact_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('contacts', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'mill_article_no' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:255',
            'fabric_sku' => 'nullable|string|max:255',
            'fds_date' => 'nullable|date',
            'swatch_submit_date' => 'nullable|date',
            'season_department' => 'nullable|string|max:255',
            'pattern_color_name_number' => 'nullable|string|max:255',
            'mill_pattern_color' => 'nullable|array',
            'mill_pattern_color.*' => 'nullable|string|max:255',
            'performance_claims' => 'nullable|string',
            'dyeing_technique' => 'nullable|string|max:255',
            'submit_type' => 'nullable|string|max:255',
            'construction_ypi' => 'nullable|string|max:255',
            'fabric_finish' => 'nullable|string|max:255',
            'care_label' => 'nullable|string',

            'construction_type' => 'nullable|string|max:100',
            'construction_type_other' => 'nullable|string|max:100',
            'weave_pattern' => 'nullable|string|max:255',
            'yarn_count_denier' => 'nullable|string|max:255',
            'elongation' => 'nullable|string|max:255',
            'growth' => 'nullable|string|max:255',
            'recovery' => 'nullable|string|max:255',
            'elongation_25_fixed' => 'nullable|string|max:255',
            'wool_type' => 'nullable|string|max:255',
            'raw_material_origin' => 'nullable|string|max:255',
            'dyeing_type' => 'nullable|string|max:255',
            'fds_season' => 'nullable|string|max:255',

            'weight_gsm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'shrinkage_percent' => 'nullable|numeric|min:0',
            'usable_width_inch' => 'nullable|numeric|min:0',
            'price_500_yds' => 'nullable|numeric|min:0',
            'price_3k' => 'nullable|numeric|min:0',
            'price_10k' => 'nullable|numeric|min:0',
            'price_25k' => 'nullable|numeric|min:0',
            'price_50k_plus' => 'nullable|numeric|min:0',
            'minimum_color_quantity' => 'nullable|numeric|min:0',
            'monthly_capacity' => 'nullable|numeric|min:0',

            'price_per_meter' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:50',
            'minimum_order_quantity' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:255',

            'sample_lead_time_days' => 'nullable|integer|min:0',
            'bulk_lead_time_days' => 'nullable|integer|min:0',
            'shipment_mode' => 'nullable|string|max:100',
            'port_of_loading' => 'nullable|string|max:255',

            'color_fastness' => 'nullable|string|max:255',
            'abrasion_resistance' => 'nullable|string|max:255',
            'handfeel_drape' => 'nullable|string|max:255',
            'finish_treatments' => 'nullable|string',
            'certifications' => 'nullable|string',

            'notification_email' => 'nullable|boolean',
            'notification_phone' => 'nullable|boolean',

            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'avatar_remove' => 'nullable|boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:5120',
        ];
    }

    protected function prepareForValidation()
    {
        $millPatternColor = $this->input('mill_pattern_color');
        if (is_array($millPatternColor)) {
            $millPatternColor = array_values(array_filter(array_map('trim', $millPatternColor)));
            $this->merge(['mill_pattern_color' => $millPatternColor]);
        }

        $this->merge([
            'notification_email' => $this->boolean('notification_email'),
            'notification_phone' => $this->boolean('notification_phone'),
            'avatar_remove' => $this->boolean('avatar_remove'),
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requestedSupplierIds = array_values(array_unique(array_filter(array_map(function ($supplier_id) {
                return is_numeric($supplier_id) ? (int) $supplier_id : null;
            }, (array) $this->input('supplier_contact_ids', [])))));

            if (empty($requestedSupplierIds)) {
                return;
            }

            $business_id = $this->session()->get('user.business_id');

            $validSupplierCount = Contact::where('business_id', $business_id)
                ->whereIn('type', ['supplier', 'both'])
                ->whereIn('id', $requestedSupplierIds)
                ->count();

            if ($validSupplierCount !== count($requestedSupplierIds)) {
                $validator->errors()->add('supplier_contact_ids', __('projectx::lang.invalid_supplier_selection'));
            }
        });
    }
}
