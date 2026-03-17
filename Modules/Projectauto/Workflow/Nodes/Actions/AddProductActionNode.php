<?php

namespace Modules\Projectauto\Workflow\Nodes\Actions;

use App\Utils\Util;
use Modules\Projectauto\Workflow\Nodes\AbstractWorkflowNode;

class AddProductActionNode extends AbstractWorkflowNode
{
    public function definition(): array
    {
        return [
            'type' => 'add_product',
            'family' => 'action',
            'label' => __('projectauto::lang.add_product_action'),
            'description' => __('projectauto::lang.action_add_product_description'),
            'config_schema' => [
                ['key' => 'name', 'label' => __('projectauto::lang.name'), 'type' => 'text', 'required' => true],
                [
                    'key' => 'type',
                    'label' => __('projectauto::lang.product_type'),
                    'type' => 'select',
                    'required' => false,
                    'options' => [
                        ['value' => 'single', 'label' => __('projectauto::lang.product_type_single')],
                        ['value' => 'variable', 'label' => __('projectauto::lang.product_type_variable')],
                        ['value' => 'combo', 'label' => __('projectauto::lang.product_type_combo')],
                    ],
                ],
                ['key' => 'unit_id', 'label' => __('projectauto::lang.unit_id'), 'type' => 'integer', 'required' => true],
                ['key' => 'brand_id', 'label' => __('projectauto::lang.brand_id'), 'type' => 'integer', 'required' => false],
                ['key' => 'category_id', 'label' => __('projectauto::lang.category_id'), 'type' => 'integer', 'required' => false],
                ['key' => 'sub_category_id', 'label' => __('projectauto::lang.sub_category_id'), 'type' => 'integer', 'required' => false],
                ['key' => 'tax', 'label' => __('projectauto::lang.tax'), 'type' => 'integer', 'required' => false],
                ['key' => 'sku', 'label' => __('projectauto::lang.sku'), 'type' => 'text', 'required' => false],
                [
                    'key' => 'barcode_type',
                    'label' => __('projectauto::lang.barcode_type'),
                    'type' => 'select',
                    'required' => false,
                    'options' => $this->barcodeTypeOptions(),
                ],
                ['key' => 'alert_quantity', 'label' => __('projectauto::lang.alert_quantity'), 'type' => 'number', 'required' => false],
                [
                    'key' => 'tax_type',
                    'label' => __('projectauto::lang.tax_type'),
                    'type' => 'select',
                    'required' => false,
                    'options' => [
                        ['value' => 'inclusive', 'label' => __('projectauto::lang.tax_type_inclusive')],
                        ['value' => 'exclusive', 'label' => __('projectauto::lang.tax_type_exclusive')],
                    ],
                ],
                ['key' => 'product_description', 'label' => __('projectauto::lang.product_description'), 'type' => 'textarea', 'required' => false],
                ['key' => 'enable_stock', 'label' => __('projectauto::lang.enable_stock'), 'type' => 'boolean', 'required' => false],
                ['key' => 'not_for_selling', 'label' => __('projectauto::lang.not_for_selling'), 'type' => 'boolean', 'required' => false],
                [
                    'key' => 'product_locations',
                    'label' => __('projectauto::lang.product_locations'),
                    'type' => 'repeater',
                    'required' => false,
                    'children' => [
                        ['key' => 'value', 'label' => __('projectauto::lang.location_id'), 'type' => 'integer', 'required' => true],
                    ],
                ],
                ['key' => 'single_dpp', 'label' => __('projectauto::lang.single_dpp'), 'type' => 'number', 'required' => true],
                ['key' => 'single_dpp_inc_tax', 'label' => __('projectauto::lang.single_dpp_inc_tax'), 'type' => 'number', 'required' => true],
                ['key' => 'profit_percent', 'label' => __('projectauto::lang.profit_percent'), 'type' => 'number', 'required' => false],
                ['key' => 'single_dsp', 'label' => __('projectauto::lang.single_dsp'), 'type' => 'number', 'required' => true],
                ['key' => 'single_dsp_inc_tax', 'label' => __('projectauto::lang.single_dsp_inc_tax'), 'type' => 'number', 'required' => true],
            ],
            'ports' => [
                'inputs' => ['input'],
            ],
        ];
    }

    protected function barcodeTypeOptions(): array
    {
        return collect(app(Util::class)->barcode_types())
            ->map(function ($label, $value) {
                return [
                    'value' => $value,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }
}
