<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Services\Inventory\InventoryCostService;
use Tests\TestCase;

class InventoryCostServiceTest extends TestCase
{
    public function test_it_maps_goods_receipt_to_inbound_inventory_profile(): void
    {
        $service = new InventoryCostService();
        $document = new FinanceDocument(['document_type' => 'goods_receipt']);

        $profile = $service->resolveDocumentProfile($document);

        $this->assertSame('receipt', $profile['movement_type']);
        $this->assertSame('in', $profile['direction']);
    }

    public function test_it_builds_weighted_average_receipt_movement_plan(): void
    {
        $service = new InventoryCostService();
        $document = $this->document('goods_receipt', [
            new FinanceDocumentLine([
                'id' => 11,
                'product_id' => 5,
                'quantity' => 10,
                'line_amount' => 500,
            ]),
        ]);

        $plan = $service->buildMovementPlans($document);

        $this->assertCount(1, $plan['movements']);
        $this->assertSame('receipt', $plan['movements'][0]['movement_type']);
        $this->assertSame('50.0000', $plan['movements'][0]['unit_cost']);
        $this->assertSame('500.0000', $plan['movements'][0]['total_cost']);
        $this->assertSame('10.0000', $plan['layers'][0]['quantity_on_hand']);
        $this->assertSame('500.0000', $plan['layers'][0]['total_value_on_hand']);
    }

    public function test_it_builds_delivery_issue_plan_using_weighted_average_pool(): void
    {
        $service = new InventoryCostService();
        $document = $this->document('delivery', [
            new FinanceDocumentLine([
                'id' => 12,
                'product_id' => 5,
                'quantity' => 4,
                'line_amount' => 0,
            ]),
        ]);

        $plan = $service->buildMovementPlans($document, [
            '1|5|10|VND' => [
                'business_id' => 1,
                'product_id' => 5,
                'business_location_id' => 10,
                'currency_code' => 'VND',
                'costing_method' => 'weighted_average',
                'layer_type' => 'weighted_average_pool',
                'quantity_in' => '10.0000',
                'quantity_out' => '0.0000',
                'quantity_on_hand' => '10.0000',
                'total_value_in' => '500.0000',
                'total_value_out' => '0.0000',
                'total_value_on_hand' => '500.0000',
                'average_unit_cost' => '50.0000',
            ],
        ]);

        $this->assertCount(1, $plan['movements']);
        $this->assertSame('issue', $plan['movements'][0]['movement_type']);
        $this->assertSame('out', $plan['movements'][0]['direction']);
        $this->assertSame('50.0000', $plan['movements'][0]['unit_cost']);
        $this->assertSame('200.0000', $plan['movements'][0]['total_cost']);
        $this->assertSame('6.0000', $plan['layers'][0]['quantity_on_hand']);
        $this->assertSame('300.0000', $plan['layers'][0]['total_value_on_hand']);
    }

    public function test_it_blocks_issue_plan_when_negative_stock_is_not_allowed(): void
    {
        $service = new InventoryCostService();
        $document = $this->document('delivery', [
            new FinanceDocumentLine([
                'id' => 13,
                'product_id' => 5,
                'quantity' => 12,
                'line_amount' => 0,
            ]),
        ]);

        $this->expectException(\RuntimeException::class);

        $service->buildMovementPlans($document, [
            '1|5|10|VND' => [
                'business_id' => 1,
                'product_id' => 5,
                'business_location_id' => 10,
                'currency_code' => 'VND',
                'costing_method' => 'weighted_average',
                'layer_type' => 'weighted_average_pool',
                'quantity_in' => '10.0000',
                'quantity_out' => '0.0000',
                'quantity_on_hand' => '10.0000',
                'total_value_in' => '500.0000',
                'total_value_out' => '0.0000',
                'total_value_on_hand' => '500.0000',
                'average_unit_cost' => '50.0000',
            ],
        ]);
    }

    protected function document(string $documentType, array $lines): FinanceDocument
    {
        $document = new FinanceDocument([
            'business_id' => 1,
            'document_family' => in_array($documentType, ['goods_receipt'], true) ? 'procurement' : 'sales',
            'document_type' => $documentType,
            'currency_code' => 'VND',
            'business_location_id' => 10,
            'document_date' => '2026-03-31',
            'posting_date' => '2026-03-31',
        ]);
        $document->setRelation('lines', collect($lines));

        return $document;
    }
}
