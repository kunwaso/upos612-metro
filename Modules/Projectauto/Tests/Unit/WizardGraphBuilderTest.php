<?php

namespace Modules\Projectauto\Tests\Unit;

use Modules\Projectauto\Utils\WizardGraphBuilder;
use Tests\TestCase;

class WizardGraphBuilderTest extends TestCase
{
    public function test_builds_graph_with_condition_spec_and_actions()
    {
        $builder = new WizardGraphBuilder();

        $graph = $builder->build([
            'trigger_type' => 'payment_status_updated',
            'condition' => [
                'field' => 'payment_status',
                'operator' => 'equals',
                'value' => 'paid',
            ],
            'actions' => [
                ['type' => 'create_invoice', 'config' => ['location_id' => 1, 'contact_id' => 2, 'products' => [['product_id' => 1, 'variation_id' => 2, 'quantity' => 1, 'unit_price_inc_tax' => 10]]]],
                ['type' => 'adjust_stock', 'config' => ['location_id' => 1, 'products' => [['product_id' => 1, 'variation_id' => 2, 'quantity' => 1, 'unit_price' => 10]]]],
            ],
        ]);

        $this->assertCount(4, $graph['nodes']);
        $this->assertSame('logic.if_else', $graph['nodes'][1]['type']);
        $this->assertSame('payment_status', $graph['nodes'][1]['config']['condition_spec']['field']);
        $this->assertCount(3, $graph['edges']);
        $this->assertSame('true', $graph['edges'][1]['source_port']);
    }

    public function test_builds_direct_action_graph_without_condition()
    {
        $builder = new WizardGraphBuilder();

        $graph = $builder->build([
            'trigger_type' => 'sales_order_status_updated',
            'actions' => [
                ['type' => 'add_product', 'config' => ['name' => 'Sample', 'unit_id' => 1, 'single_dpp' => 10, 'single_dpp_inc_tax' => 11, 'single_dsp' => 15, 'single_dsp_inc_tax' => 16]],
            ],
        ]);

        $this->assertCount(2, $graph['nodes']);
        $this->assertCount(1, $graph['edges']);
        $this->assertSame('next', $graph['edges'][0]['source_port']);
    }
}
