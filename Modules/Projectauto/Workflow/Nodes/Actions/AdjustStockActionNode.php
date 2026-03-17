<?php

namespace Modules\Projectauto\Workflow\Nodes\Actions;

use Modules\Projectauto\Workflow\Nodes\AbstractWorkflowNode;

class AdjustStockActionNode extends AbstractWorkflowNode
{
    public function definition(): array
    {
        return [
            'type' => 'adjust_stock',
            'family' => 'action',
            'label' => __('projectauto::lang.adjust_stock_action'),
            'description' => __('projectauto::lang.action_adjust_stock_description'),
            'config_schema' => [
                ['key' => 'location_id', 'label' => __('projectauto::lang.location_id'), 'type' => 'integer', 'required' => true],
                ['key' => 'transaction_date', 'label' => __('projectauto::lang.transaction_date'), 'type' => 'date', 'required' => false],
                [
                    'key' => 'adjustment_type',
                    'label' => __('projectauto::lang.adjustment_type'),
                    'type' => 'select',
                    'required' => false,
                    'options' => [
                        ['value' => 'normal', 'label' => __('projectauto::lang.adjustment_type_normal')],
                        ['value' => 'abnormal', 'label' => __('projectauto::lang.adjustment_type_abnormal')],
                    ],
                ],
                ['key' => 'additional_notes', 'label' => __('projectauto::lang.additional_notes'), 'type' => 'textarea', 'required' => false],
                ['key' => 'total_amount_recovered', 'label' => __('projectauto::lang.total_amount_recovered'), 'type' => 'number', 'required' => false],
                ['key' => 'ref_no', 'label' => __('projectauto::lang.ref_no'), 'type' => 'text', 'required' => false],
                [
                    'key' => 'products',
                    'label' => __('projectauto::lang.products'),
                    'type' => 'repeater',
                    'required' => true,
                    'min_items' => 1,
                    'children' => [
                        ['key' => 'product_id', 'label' => __('projectauto::lang.product_id'), 'type' => 'integer', 'required' => true],
                        ['key' => 'variation_id', 'label' => __('projectauto::lang.variation_id'), 'type' => 'integer', 'required' => true],
                        ['key' => 'quantity', 'label' => __('projectauto::lang.quantity'), 'type' => 'number', 'required' => true],
                        ['key' => 'unit_price', 'label' => __('projectauto::lang.unit_price'), 'type' => 'number', 'required' => true],
                        ['key' => 'lot_no_line_id', 'label' => __('projectauto::lang.lot_no_line_id'), 'type' => 'integer', 'required' => false],
                    ],
                ],
            ],
            'ports' => [
                'inputs' => ['input'],
            ],
        ];
    }
}
