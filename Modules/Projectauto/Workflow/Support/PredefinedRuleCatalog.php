<?php

namespace Modules\Projectauto\Workflow\Support;

use Modules\Projectauto\Workflow\NodeRegistry;

class PredefinedRuleCatalog
{
    protected NodeRegistry $nodeRegistry;

    public function __construct(NodeRegistry $nodeRegistry)
    {
        $this->nodeRegistry = $nodeRegistry;
    }

    public function catalog(): array
    {
        $definitions = $this->nodeRegistry->definitions();
        $triggers = [];
        $logic = [];
        $actions = [];

        foreach ($definitions as $definition) {
            if ($definition['family'] === 'trigger') {
                $triggers[$definition['type']] = $definition;
            } elseif ($definition['family'] === 'logic') {
                $logic[$definition['type']] = $definition;
            } elseif ($definition['family'] === 'action') {
                $actions[$definition['type']] = $definition;
            }
        }

        return [
            'families' => ['trigger', 'logic', 'action'],
            'triggers' => $triggers,
            'logic' => $logic,
            'actions' => $actions,
            'operators' => $this->operators(),
            'conditions' => $this->conditionFields(),
        ];
    }

    public function trigger(string $type): ?array
    {
        return $this->catalog()['triggers'][$type] ?? null;
    }

    public function action(string $type): ?array
    {
        return $this->catalog()['actions'][$type] ?? null;
    }

    public function conditionField(string $field): ?array
    {
        return $this->catalog()['conditions'][$field] ?? null;
    }

    protected function operators(): array
    {
        return [
            'equals' => ['label' => __('projectauto::lang.operator_equals'), 'value_types' => ['string', 'number', 'boolean']],
            'not_equals' => ['label' => __('projectauto::lang.operator_not_equals'), 'value_types' => ['string', 'number', 'boolean']],
            'greater_than' => ['label' => __('projectauto::lang.operator_greater_than'), 'value_types' => ['number']],
            'less_than' => ['label' => __('projectauto::lang.operator_less_than'), 'value_types' => ['number']],
            'contains' => ['label' => __('projectauto::lang.operator_contains'), 'value_types' => ['string']],
            'in' => ['label' => __('projectauto::lang.operator_in'), 'value_types' => ['string', 'number']],
            'not_in' => ['label' => __('projectauto::lang.operator_not_in'), 'value_types' => ['string', 'number']],
        ];
    }

    protected function conditionFields(): array
    {
        return [
            'old_status' => [
                'label' => __('projectauto::lang.condition_old_status'),
                'value_type' => 'string',
                'supported_triggers' => ['payment_status_updated', 'sales_order_status_updated'],
            ],
            'new_status' => [
                'label' => __('projectauto::lang.condition_new_status'),
                'value_type' => 'string',
                'supported_triggers' => ['payment_status_updated', 'sales_order_status_updated'],
            ],
            'payment_status' => [
                'label' => __('projectauto::lang.condition_payment_status'),
                'value_type' => 'string',
                'supported_triggers' => ['payment_status_updated', 'sales_order_status_updated'],
                'options' => [
                    ['value' => 'paid', 'label' => __('projectauto::lang.payment_status_paid')],
                    ['value' => 'partial', 'label' => __('projectauto::lang.payment_status_partial')],
                    ['value' => 'due', 'label' => __('projectauto::lang.payment_status_due')],
                    ['value' => 'overdue', 'label' => __('projectauto::lang.payment_status_overdue')],
                ],
            ],
            'transaction_type' => [
                'label' => __('projectauto::lang.condition_transaction_type'),
                'value_type' => 'string',
                'supported_triggers' => ['payment_status_updated', 'sales_order_status_updated'],
                'options' => [
                    ['value' => 'sell', 'label' => 'sell'],
                    ['value' => 'sales_order', 'label' => 'sales_order'],
                    ['value' => 'purchase', 'label' => 'purchase'],
                ],
            ],
            'location_id' => [
                'label' => __('projectauto::lang.condition_location_id'),
                'value_type' => 'number',
                'supported_triggers' => ['payment_status_updated', 'sales_order_status_updated'],
            ],
            'contact_id' => [
                'label' => __('projectauto::lang.condition_contact_id'),
                'value_type' => 'number',
                'supported_triggers' => ['payment_status_updated', 'sales_order_status_updated'],
            ],
            'source_transaction_id' => [
                'label' => __('projectauto::lang.condition_source_transaction_id'),
                'value_type' => 'number',
                'supported_triggers' => ['payment_status_updated', 'sales_order_status_updated'],
            ],
        ];
    }
}
