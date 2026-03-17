<?php

namespace Modules\Projectauto\Workflow\Nodes\Actions;

use App\Utils\Util;
use Modules\Projectauto\Workflow\Nodes\AbstractWorkflowNode;

class CreateInvoiceActionNode extends AbstractWorkflowNode
{
    public function definition(): array
    {
        return [
            'type' => 'create_invoice',
            'family' => 'action',
            'label' => __('projectauto::lang.create_invoice_action'),
            'description' => __('projectauto::lang.action_create_invoice_description'),
            'config_schema' => [
                ['key' => 'location_id', 'label' => __('projectauto::lang.location_id'), 'type' => 'integer', 'required' => true],
                ['key' => 'contact_id', 'label' => __('projectauto::lang.contact_id'), 'type' => 'integer', 'required' => true],
                ['key' => 'transaction_date', 'label' => __('projectauto::lang.transaction_date'), 'type' => 'date', 'required' => false],
                [
                    'key' => 'status',
                    'label' => __('projectauto::lang.status'),
                    'type' => 'select',
                    'required' => false,
                    'options' => [
                        ['value' => 'final', 'label' => __('projectauto::lang.status_final')],
                        ['value' => 'draft', 'label' => __('projectauto::lang.status_draft')],
                    ],
                ],
                ['key' => 'tax_id', 'label' => __('projectauto::lang.tax_id'), 'type' => 'integer', 'required' => false],
                [
                    'key' => 'discount_type',
                    'label' => __('projectauto::lang.discount_type'),
                    'type' => 'select',
                    'required' => false,
                    'options' => [
                        ['value' => 'fixed', 'label' => __('projectauto::lang.discount_type_fixed')],
                        ['value' => 'percentage', 'label' => __('projectauto::lang.discount_type_percentage')],
                    ],
                ],
                ['key' => 'discount_amount', 'label' => __('projectauto::lang.discount_amount'), 'type' => 'number', 'required' => false],
                ['key' => 'invoice_no', 'label' => __('projectauto::lang.invoice_no'), 'type' => 'text', 'required' => false],
                ['key' => 'sale_note', 'label' => __('projectauto::lang.sale_note'), 'type' => 'textarea', 'required' => false],
                ['key' => 'staff_note', 'label' => __('projectauto::lang.staff_note'), 'type' => 'textarea', 'required' => false],
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
                        ['key' => 'unit_price_inc_tax', 'label' => __('projectauto::lang.unit_price_inc_tax'), 'type' => 'number', 'required' => true],
                        ['key' => 'unit_price', 'label' => __('projectauto::lang.unit_price'), 'type' => 'number', 'required' => false],
                        ['key' => 'item_tax', 'label' => __('projectauto::lang.item_tax'), 'type' => 'number', 'required' => false],
                        ['key' => 'tax_id', 'label' => __('projectauto::lang.tax_id'), 'type' => 'integer', 'required' => false],
                        [
                            'key' => 'line_discount_type',
                            'label' => __('projectauto::lang.line_discount_type'),
                            'type' => 'select',
                            'required' => false,
                            'options' => [
                                ['value' => 'fixed', 'label' => __('projectauto::lang.discount_type_fixed')],
                                ['value' => 'percentage', 'label' => __('projectauto::lang.discount_type_percentage')],
                            ],
                        ],
                        ['key' => 'line_discount_amount', 'label' => __('projectauto::lang.line_discount_amount'), 'type' => 'number', 'required' => false],
                        ['key' => 'lot_no_line_id', 'label' => __('projectauto::lang.lot_no_line_id'), 'type' => 'integer', 'required' => false],
                    ],
                ],
                [
                    'key' => 'payments',
                    'label' => __('projectauto::lang.payments'),
                    'type' => 'repeater',
                    'required' => false,
                    'children' => [
                        ['key' => 'amount', 'label' => __('projectauto::lang.amount'), 'type' => 'number', 'required' => true],
                        [
                            'key' => 'method',
                            'label' => __('projectauto::lang.payment_method'),
                            'type' => 'select',
                            'required' => true,
                            'options' => $this->paymentMethodOptions(),
                        ],
                        ['key' => 'paid_on', 'label' => __('projectauto::lang.paid_on'), 'type' => 'date', 'required' => false],
                        ['key' => 'note', 'label' => __('projectauto::lang.note'), 'type' => 'textarea', 'required' => false],
                        ['key' => 'account_id', 'label' => __('projectauto::lang.account_id'), 'type' => 'integer', 'required' => false],
                    ],
                ],
            ],
            'ports' => [
                'inputs' => ['input'],
            ],
        ];
    }

    protected function paymentMethodOptions(): array
    {
        return collect(app(Util::class)->payment_types())
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
