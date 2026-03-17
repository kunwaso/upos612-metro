<?php

namespace Modules\Projectauto\Workflow\Support;

use App\BusinessLocation;
use App\Contact;
use App\Product;

class WorkflowDefinitionResolver
{
    protected PredefinedRuleCatalog $catalog;

    public function __construct(PredefinedRuleCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function resolve(): array
    {
        $catalog = $this->catalog->catalog();
        $dynamicOptions = $this->dynamicOptions();

        return [
            'families' => $catalog['families'],
            'triggers' => array_values($catalog['triggers']),
            'logic' => array_values($catalog['logic']),
            'actions' => array_values($this->withDynamicOptions($catalog['actions'], $dynamicOptions)),
            'operators' => $catalog['operators'],
            'conditions' => $catalog['conditions'],
        ];
    }

    protected function dynamicOptions(): array
    {
        $businessId = (int) (request()->session()->get('user.business_id') ?? 0);
        if ($businessId <= 0) {
            return [];
        }

        return [
            'location_id' => $this->locationOptions($businessId),
            'contact_id' => $this->contactOptions($businessId),
            'product_id' => $this->productOptions($businessId),
        ];
    }

    protected function withDynamicOptions(array $definitions, array $dynamicOptions): array
    {
        foreach ($definitions as &$definition) {
            $definition['config_schema'] = $this->injectOptions($definition['config_schema'] ?? [], $dynamicOptions);
        }

        return $definitions;
    }

    protected function injectOptions(array $schema, array $dynamicOptions): array
    {
        foreach ($schema as &$field) {
            $key = $field['key'] ?? null;
            if ($key && ! empty($dynamicOptions[$key])) {
                $field['options'] = $dynamicOptions[$key];
            }

            if (! empty($field['children']) && is_array($field['children'])) {
                $field['children'] = $this->injectOptions($field['children'], $dynamicOptions);
            }
        }

        return $schema;
    }

    protected function locationOptions(int $businessId): array
    {
        return collect(BusinessLocation::forDropdown($businessId, false, false, true, false))
            ->map(function ($label, $value) {
                return [
                    'value' => (string) $value,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    protected function contactOptions(int $businessId): array
    {
        return collect(Contact::customersDropdown($businessId, false, true))
            ->map(function ($label, $value) {
                return [
                    'value' => (string) $value,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    protected function productOptions(int $businessId): array
    {
        return Product::query()
            ->where('business_id', $businessId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(function (Product $product) {
                $label = $product->name;
                if (! empty($product->sku)) {
                    $label .= ' (' . $product->sku . ')';
                }

                return [
                    'value' => (string) $product->id,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }
}
