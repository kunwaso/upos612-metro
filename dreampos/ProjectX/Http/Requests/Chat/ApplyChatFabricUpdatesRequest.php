<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Entities\Fabric;

class ApplyChatFabricUpdatesRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.edit');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');
        $allowedFields = $this->getChatFabricWritableFields();

        return array_merge([
            'fabric_id' => [
                'required',
                'integer',
                Rule::exists('projectx_fabrics', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'message' => [
                'required',
                'integer',
                Rule::exists('projectx_chat_messages', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'updates' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) use ($allowedFields) {
                    if (! is_array($value)) {
                        return;
                    }

                    $providedFields = array_keys($value);
                    $invalidFields = array_values(array_diff($providedFields, $allowedFields));
                    if (! empty($invalidFields)) {
                        $fail(__('projectx::lang.chat_apply_invalid_update_fields'));
                    }

                    if (empty(array_intersect($allowedFields, $providedFields))) {
                        $fail(__('projectx::lang.chat_apply_no_valid_updates'));
                    }
                },
            ],
        ], $this->getChatFabricUpdateRules($business_id));
    }

    public function prepareForValidation()
    {
        $this->merge([
            'fabric_id' => $this->route('fabric_id'),
            'message' => $this->route('message'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function getChatFabricWritableFieldTypes(): array
    {
        $fieldTypes = (array) config('projectx.chat.fabric_updates.writable_field_types', []);
        $allowedTypes = ['string', 'decimal', 'integer', 'boolean', 'date'];
        $normalized = [];

        foreach ($fieldTypes as $field => $type) {
            $field = trim((string) $field);
            $type = strtolower(trim((string) $type));

            if ($field === '' || ! in_array($type, $allowedTypes, true)) {
                continue;
            }

            $normalized[$field] = $type;
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    protected function getChatFabricWritableFields(): array
    {
        return array_keys($this->getChatFabricWritableFieldTypes());
    }

    protected function getChatFabricUpdateRules(int $business_id): array
    {
        return [
            'updates.name' => ['sometimes', 'string', 'max:255'],
            'updates.status' => ['sometimes', 'string', Rule::in(Fabric::STATUSES)],
            'updates.fiber' => ['sometimes', 'nullable', 'string', 'max:255'],

            'updates.purchase_price' => ['sometimes', 'numeric', 'min:0'],
            'updates.sale_price' => ['sometimes', 'numeric', 'min:0'],
            'updates.weight_gsm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.width_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.shrinkage_percent' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.usable_width_inch' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.price_per_meter' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.minimum_order_quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.price_500_yds' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.price_3k' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.price_10k' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.price_25k' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.price_50k_plus' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.minimum_color_quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'updates.monthly_capacity' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'updates.supplier_contact_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id)
                        ->whereIn('type', ['supplier', 'both']);
                }),
            ],
            'updates.progress_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'updates.sample_lead_time_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'updates.bulk_lead_time_days' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'updates.notification_email' => ['sometimes', 'boolean'],
            'updates.notification_phone' => ['sometimes', 'boolean'],

            'updates.due_date' => ['sometimes', 'nullable', 'date'],
            'updates.fds_date' => ['sometimes', 'nullable', 'date'],
            'updates.swatch_submit_date' => ['sometimes', 'nullable', 'date'],

            'updates.description' => ['sometimes', 'nullable', 'string'],
            'updates.supplier_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.supplier_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'updates.mill_article_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.country_of_origin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.fabric_sku' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.construction_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'updates.construction_type_other' => ['sometimes', 'nullable', 'string', 'max:100'],
            'updates.weave_pattern' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.yarn_count_denier' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.currency' => ['sometimes', 'nullable', 'string', 'max:50'],
            'updates.payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.shipment_mode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'updates.port_of_loading' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.color_fastness' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.abrasion_resistance' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.handfeel_drape' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.finish_treatments' => ['sometimes', 'nullable', 'string'],
            'updates.certifications' => ['sometimes', 'nullable', 'string'],
            'updates.season_department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.pattern_color_name_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.performance_claims' => ['sometimes', 'nullable', 'string'],
            'updates.dyeing_technique' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.submit_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.construction_ypi' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.fabric_finish' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.care_label' => ['sometimes', 'nullable', 'string'],
            'updates.elongation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.growth' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.recovery' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.elongation_25_fixed' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.wool_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.raw_material_origin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.dyeing_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'updates.fds_season' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
