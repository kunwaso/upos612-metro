<?php

namespace Modules\Projectauto\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Modules\Projectauto\Workflow\NodeRegistry;
use Modules\Projectauto\Workflow\Support\ConditionSpecificationValidator;
use Modules\Projectauto\Workflow\Support\NodeConfigValidator;
use Modules\Projectauto\Workflow\Support\PredefinedRuleCatalog;
use Modules\Projectauto\Workflow\Support\WorkflowGraphValidator;
use Tests\TestCase;

class WorkflowGraphValidatorTest extends TestCase
{
    public function test_rejects_multiple_triggers()
    {
        $validator = $this->makeValidator();

        $this->expectException(ValidationException::class);

        $validator->validate([
            'nodes' => [
                ['id' => 'trigger_a', 'type' => 'payment_status_updated', 'family' => 'trigger', 'config' => []],
                ['id' => 'trigger_b', 'type' => 'sales_order_status_updated', 'family' => 'trigger', 'config' => []],
            ],
            'edges' => [],
        ], true);
    }

    public function test_accepts_single_trigger_condition_and_action_graph()
    {
        $validator = $this->makeValidator();

        $graph = $validator->validate([
            'nodes' => [
                ['id' => 'trigger_1', 'type' => 'payment_status_updated', 'family' => 'trigger', 'config' => []],
                ['id' => 'condition_1', 'type' => 'logic.if_else', 'family' => 'logic', 'config' => [
                    'condition_spec' => ['field' => 'payment_status', 'operator' => 'equals', 'value' => 'paid'],
                ]],
                ['id' => 'action_1', 'type' => 'create_invoice', 'family' => 'action', 'config' => [
                    'location_id' => 1,
                    'contact_id' => 2,
                    'products' => [['product_id' => 1, 'variation_id' => 2, 'quantity' => 1, 'unit_price_inc_tax' => 10]],
                ]],
            ],
            'edges' => [
                ['source' => 'trigger_1', 'source_port' => 'next', 'target' => 'condition_1', 'target_port' => 'input'],
                ['source' => 'condition_1', 'source_port' => 'true', 'target' => 'action_1', 'target_port' => 'input'],
            ],
        ], true);

        $this->assertSame('payment_status_updated', $graph['nodes'][0]['type']);
    }

    public function test_rejects_direct_action_links_when_condition_node_exists()
    {
        $validator = $this->makeValidator();

        $this->expectException(ValidationException::class);

        $validator->validate([
            'nodes' => [
                ['id' => 'trigger_1', 'type' => 'payment_status_updated', 'family' => 'trigger', 'config' => []],
                ['id' => 'condition_1', 'type' => 'logic.if_else', 'family' => 'logic', 'config' => [
                    'condition_spec' => ['field' => 'payment_status', 'operator' => 'equals', 'value' => 'paid'],
                ]],
                ['id' => 'action_1', 'type' => 'create_invoice', 'family' => 'action', 'config' => [
                    'location_id' => 1,
                    'contact_id' => 2,
                    'products' => [['product_id' => 1, 'variation_id' => 2, 'quantity' => 1, 'unit_price_inc_tax' => 10]],
                ]],
            ],
            'edges' => [
                ['source' => 'trigger_1', 'source_port' => 'next', 'target' => 'condition_1', 'target_port' => 'input'],
                ['source' => 'trigger_1', 'source_port' => 'next', 'target' => 'action_1', 'target_port' => 'input'],
            ],
        ], false);
    }

    protected function makeValidator(): WorkflowGraphValidator
    {
        $catalog = new PredefinedRuleCatalog(new NodeRegistry());

        return new WorkflowGraphValidator(
            $catalog,
            new NodeConfigValidator(),
            new ConditionSpecificationValidator($catalog)
        );
    }
}
