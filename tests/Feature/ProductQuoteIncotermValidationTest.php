<?php

namespace Tests\Feature;

use App\Http\Requests\StoreProductBudgetQuoteRequest;
use App\Http\Requests\StoreProductQuoteRequest;
use App\Http\Requests\UpdateProductQuoteRequest;
use App\Utils\ProductCostingUtil;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Tests\TestCase;

class ProductQuoteIncotermValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_request_allows_blank_incoterm_for_local_delivery()
    {
        $validator = $this->validateFormRequest(
            StoreProductQuoteRequest::class,
            [
                'shipment_port' => '',
                'lines' => [[
                    'product_id' => 1,
                    'line_type' => 'fabric',
                    'currency' => 'USD',
                    'incoterm' => '',
                ]],
            ],
            ['lines' => 'array']
        );

        $this->assertTrue($validator->passes());
    }

    public function test_store_request_requires_incoterm_for_non_local_delivery()
    {
        $validator = $this->validateFormRequest(
            StoreProductQuoteRequest::class,
            [
                'shipment_port' => 'Bangkok',
                'lines' => [[
                    'product_id' => 1,
                    'line_type' => 'fabric',
                    'currency' => 'USD',
                    'incoterm' => '',
                ]],
            ],
            ['lines' => 'array']
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('lines.0.incoterm'));
    }

    public function test_store_request_rejects_unknown_incoterm_for_non_local_delivery()
    {
        $validator = $this->validateFormRequest(
            StoreProductQuoteRequest::class,
            [
                'shipment_port' => 'Shanghai',
                'lines' => [[
                    'product_id' => 1,
                    'line_type' => 'fabric',
                    'currency' => 'USD',
                    'incoterm' => 'INVALID',
                ]],
            ],
            ['lines' => 'array']
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('lines.0.incoterm'));
    }

    public function test_update_request_requires_incoterm_for_non_local_delivery()
    {
        $validator = $this->validateFormRequest(
            UpdateProductQuoteRequest::class,
            [
                'shipment_port' => 'Bangkok',
                'lines' => [[
                    'product_id' => 1,
                    'line_type' => 'fabric',
                    'currency' => 'USD',
                    'incoterm' => '',
                ]],
            ],
            ['lines' => 'array']
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('lines.0.incoterm'));
    }

    public function test_budget_request_applies_local_delivery_incoterm_rule()
    {
        $localValidator = $this->validateFormRequest(
            StoreProductBudgetQuoteRequest::class,
            [
                'shipment_port' => '',
                'incoterm' => '',
            ],
            []
        );
        $nonLocalValidator = $this->validateFormRequest(
            StoreProductBudgetQuoteRequest::class,
            [
                'shipment_port' => 'Bangkok',
                'incoterm' => '',
            ],
            []
        );

        $this->assertTrue($localValidator->passes());
        $this->assertFalse($nonLocalValidator->passes());
        $this->assertTrue($nonLocalValidator->errors()->has('incoterm'));
    }

    protected function validateFormRequest(string $requestClass, array $payload, array $rules): ValidatorContract
    {
        $this->bindCostingUtil();

        /** @var \Illuminate\Foundation\Http\FormRequest $request */
        $request = $requestClass::create('/product/quotes/test', 'POST', $payload);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user.business_id', 1);
        $request->setLaravelSession($session);

        $validator = Validator::make($request->all(), $rules);
        $request->withValidator($validator);
        $validator->passes();

        return $validator;
    }

    protected function bindCostingUtil(): void
    {
        $costingUtil = Mockery::mock(ProductCostingUtil::class);
        $costingUtil->shouldReceive('getDropdownOptions')->andReturn([
            'currency' => ['USD' => 'USD'],
            'incoterm' => ['FOB', 'CIF', 'LOCAL'],
            'purchase_uom' => ['pcs', 'yds'],
        ]);

        $this->app->instance(ProductCostingUtil::class, $costingUtil);
    }
}

