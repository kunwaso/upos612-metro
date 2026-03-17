<?php

namespace Modules\Projectauto\Workflow\Nodes\Triggers;

use Modules\Projectauto\Workflow\Nodes\AbstractWorkflowNode;

class SalesOrderStatusUpdatedNode extends AbstractWorkflowNode
{
    public function definition(): array
    {
        return [
            'type' => 'sales_order_status_updated',
            'family' => 'trigger',
            'label' => __('projectauto::lang.sales_order_status_updated'),
            'description' => __('projectauto::lang.trigger_sales_order_status_updated_description'),
            'config_schema' => [],
            'ports' => [
                'outputs' => ['next'],
            ],
        ];
    }
}
