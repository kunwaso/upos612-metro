<?php

namespace Tests\Unit;

use App\Category;
use App\Product;
use App\Unit;
use App\Utils\ProductCostingUtil;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ProductCostingUtilQuoteTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_build_line_payload_uses_product_unit_and_base_only_pricing()
    {
        $product = $this->makeProduct('yd', 'Fabric');
        $util = $this->makeCostingUtilMock();

        $payload = $util->buildLinePayload($product, [
            'qty' => 2,
            'base_mill_price' => 12.5,
            'test_cost' => 3,
            'surcharge' => 5,
            'finish_uplift_pct' => 0.2,
            'waste_pct' => 0.1,
            'currency' => 'USD',
            'incoterm' => '',
        ]);

        $this->assertSame('yd', $payload['costing_input']['purchase_uom']);
        $this->assertSame(0.0, $payload['costing_input']['test_cost']);
        $this->assertSame(0.0, $payload['costing_input']['surcharge']);
        $this->assertSame(0.0, $payload['costing_input']['finish_uplift_pct']);
        $this->assertSame(0.0, $payload['costing_input']['waste_pct']);
        $this->assertSame(12.5, $payload['costing_breakdown']['unit_cost']);
        $this->assertSame(25.0, $payload['costing_breakdown']['total_cost']);
        $this->assertSame(0.0, $payload['costing_breakdown']['test_cost']);
        $this->assertSame(0.0, $payload['costing_breakdown']['surcharge']);
        $this->assertSame(0.0, $payload['costing_breakdown']['finish_uplift_amount']);
        $this->assertSame(0.0, $payload['costing_breakdown']['waste_amount']);
        $this->assertSame('Fabric', $payload['product_snapshot']['category']);
    }

    public function test_build_line_payload_allows_blank_incoterm()
    {
        $product = $this->makeProduct('m', '');
        $util = $this->makeCostingUtilMock();

        $payload = $util->buildLinePayload($product, [
            'qty' => 1,
            'base_mill_price' => 8,
            'currency' => 'USD',
            'incoterm' => '',
        ]);

        $this->assertSame('', $payload['costing_input']['incoterm']);
    }

    public function test_build_line_payload_rejects_unknown_incoterm()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = $this->makeProduct('m', 'Cotton');
        $util = $this->makeCostingUtilMock();

        $util->buildLinePayload($product, [
            'qty' => 1,
            'base_mill_price' => 8,
            'currency' => 'USD',
            'incoterm' => 'UNKNOWN',
        ]);
    }

    protected function makeCostingUtilMock(): ProductCostingUtil
    {
        /** @var ProductCostingUtil $util */
        $util = Mockery::mock(ProductCostingUtil::class)->makePartial();
        $util->shouldReceive('getDropdownOptions')->andReturn([
            'currency' => ['USD' => 'USD'],
            'incoterm' => ['FOB', 'CIF', 'LOCAL'],
            'purchase_uom' => ['pcs', 'yds'],
        ]);

        return $util;
    }

    protected function makeProduct(string $unitShortName, string $categoryName): Product
    {
        $product = new Product();
        $product->id = 101;
        $product->business_id = 1;
        $product->name = 'Sample Product';
        $product->sku = 'SKU-101';
        $product->type = 'single';
        $product->selling_price = 0;
        $product->unit_id = 1;
        $product->category_id = 1;
        $product->setRelation('variations', new Collection());

        $unit = new Unit();
        $unit->short_name = $unitShortName;
        $product->setRelation('unit', $unit);

        $category = new Category();
        $category->name = $categoryName;
        $product->setRelation('category', $category);

        return $product;
    }
}

