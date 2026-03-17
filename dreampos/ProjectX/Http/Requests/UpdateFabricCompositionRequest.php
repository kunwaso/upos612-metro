<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Entities\FabricComponentCatalog;

class UpdateFabricCompositionRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('product.create');
    }

    public function rules()
    {
        $business_id = request()->session()->get('user.business_id');

        return [
            'items' => 'required|array',
            'items.*.catalog_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_fabric_component_catalog', 'id')->where(function ($query) use ($business_id) {
                    $query->where(function ($subQuery) use ($business_id) {
                        $subQuery->whereNull('business_id')
                            ->orWhere('business_id', $business_id);
                    });
                }),
            ],
            'items.*.label_override' => 'nullable|string|max:255',
            'items.*.percent' => 'required|numeric|min:0|max:100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            if (! is_array($items) || count($items) === 0) {
                $validator->errors()->add('items', __('projectx::lang.composition_items_required'));

                return;
            }

            $business_id = $this->session()->get('user.business_id');
            $otherCatalogId = FabricComponentCatalog::forBusiness($business_id)
                ->whereRaw('LOWER(label) = ?', ['other'])
                ->value('id');

            $seen = [];

            foreach ($items as $index => $item) {
                $catalogId = isset($item['catalog_id']) && $item['catalog_id'] !== ''
                    ? (int) $item['catalog_id']
                    : null;

                $labelOverride = trim((string) ($item['label_override'] ?? ''));
                $isOtherComponent = ! empty($otherCatalogId) && $catalogId === (int) $otherCatalogId;

                if (is_null($catalogId)) {
                    $validator->errors()->add("items.$index.catalog_id", __('projectx::lang.composition_required_error'));

                    continue;
                }

                if ($isOtherComponent && $labelOverride === '') {
                    $validator->errors()->add("items.$index.label_override", __('projectx::lang.composition_other_label_required'));

                    continue;
                }

                $uniqueKey = $isOtherComponent
                    ? $catalogId . '::' . strtolower($labelOverride)
                    : (string) $catalogId;

                if (isset($seen[$uniqueKey])) {
                    $validator->errors()->add("items.$index.catalog_id", __('projectx::lang.composition_duplicate_error'));

                    continue;
                }

                $seen[$uniqueKey] = true;
            }
        });
    }
}