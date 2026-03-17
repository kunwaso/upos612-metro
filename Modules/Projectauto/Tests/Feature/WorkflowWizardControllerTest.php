<?php

namespace Modules\Projectauto\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Modules\Projectauto\Http\Requests\Workflow\CreateWorkflowFromWizardRequest;
use Tests\TestCase;

class WorkflowWizardControllerTest extends TestCase
{
    public function test_request_validation_rejects_raw_condition_expression()
    {
        $payload = [
            'name' => 'Paid invoice automation',
            'trigger_type' => 'payment_status_updated',
            'condition_expression' => 'payment_status == "paid"',
            'actions' => [
                [
                    'type' => 'create_invoice',
                    'config' => [
                        'location_id' => 1,
                        'contact_id' => 2,
                        'products' => [
                            [
                                'product_id' => 1,
                                'variation_id' => 2,
                                'quantity' => 1,
                                'unit_price_inc_tax' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $request = CreateWorkflowFromWizardRequest::create('/projectauto/api/workflows/from-wizard', 'POST', $payload);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->merge($payload);

        $validator = Validator::make($payload, $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('condition_expression', $validator->errors()->toArray());
    }

    public function test_request_validation_accepts_structured_condition_payload()
    {
        $payload = [
            'name' => 'Paid invoice automation',
            'description' => 'Create an invoice when payment is paid.',
            'trigger_type' => 'payment_status_updated',
            'trigger_config' => [],
            'condition' => [
                'field' => 'payment_status',
                'operator' => 'equals',
                'value' => 'paid',
            ],
            'actions' => [
                [
                    'type' => 'create_invoice',
                    'config' => [
                        'location_id' => 1,
                        'contact_id' => 2,
                        'products' => [
                            [
                                'product_id' => 1,
                                'variation_id' => 2,
                                'quantity' => 1,
                                'unit_price_inc_tax' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $request = CreateWorkflowFromWizardRequest::create('/projectauto/api/workflows/from-wizard', 'POST', $payload);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->merge($payload);

        $validator = Validator::make($payload, $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray()));
    }
}
