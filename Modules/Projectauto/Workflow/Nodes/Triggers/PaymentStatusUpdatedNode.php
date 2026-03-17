<?php

namespace Modules\Projectauto\Workflow\Nodes\Triggers;

use Modules\Projectauto\Workflow\Nodes\AbstractWorkflowNode;

class PaymentStatusUpdatedNode extends AbstractWorkflowNode
{
    public function definition(): array
    {
        return [
            'type' => 'payment_status_updated',
            'family' => 'trigger',
            'label' => __('projectauto::lang.payment_status_updated'),
            'description' => __('projectauto::lang.trigger_payment_status_updated_description'),
            'config_schema' => [],
            'ports' => [
                'outputs' => ['next'],
            ],
        ];
    }
}
