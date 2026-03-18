<?php

namespace Modules\Aichat\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ProductQuoteDraft;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatProductQuoteWizardUtil;
use Modules\Aichat\Utils\ChatUtil;
use App\Utils\ProductCostingUtil;
use App\Utils\QuoteUtil;
use Tests\TestCase;

class ChatProductQuoteWizardUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('supplier_business_name')->nullable();
            $table->string('contact_id')->nullable();
            $table->string('contact_status')->default('active');
            $table->timestamps();
        });

        Schema::create('business_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('name');
            $table->string('location_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->boolean('not_for_selling')->default(false);
            $table->string('type')->default('single');
            $table->unsignedInteger('unit_id')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->timestamps();
        });

        Schema::create('aichat_product_quote_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->uuid('conversation_id');
            $table->string('flow')->default(ProductQuoteDraft::FLOW_MULTI);
            $table->string('status')->default(ProductQuoteDraft::STATUS_COLLECTING);
            $table->text('payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
        });

        \DB::table('contacts')->insert([
            'id' => 5,
            'business_id' => 99,
            'type' => 'customer',
            'name' => 'Alice',
            'supplier_business_name' => 'Alice Co',
            'contact_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('business_locations')->insert([
            'id' => 7,
            'business_id' => 99,
            'name' => 'Main Branch',
            'location_id' => 'MB',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('products')->insert([
            ['id' => 11, 'business_id' => 99, 'name' => 'Widget A', 'sku' => 'WA-1', 'type' => 'single', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'business_id' => 99, 'name' => 'Widget B', 'sku' => 'WB-1', 'type' => 'single', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $costingUtil = \Mockery::mock(ProductCostingUtil::class);
        $costingUtil->shouldReceive('getDropdownOptions')->andReturn([
            'currency' => ['USD' => 'USD'],
            'incoterm' => ['FOB', 'CIF'],
            'purchase_uom' => ['pcs'],
        ]);
        $costingUtil->shouldReceive('getDefaultCurrencyCode')->andReturn('USD');
        $this->app->instance(ProductCostingUtil::class, $costingUtil);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('aichat_product_quote_drafts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('business_locations');
        Schema::dropIfExists('contacts');
        \Mockery::close();
        parent::tearDown();
    }

    public function test_build_create_context_returns_multi_payload_for_ready_draft()
    {
        $util = $this->makeWizardUtil();

        $draft = new ProductQuoteDraft([
            'business_id' => 99,
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'payload' => [
                'flow' => 'multi',
                'contact' => ['contact_id' => 5, 'hint' => 'Alice Co - Alice'],
                'location' => ['location_id' => 7, 'hint' => 'Main Branch (MB)'],
                'quote_date' => '2026-03-18',
                'expires_at' => '2026-03-25',
                'shipment_port' => '',
                'remark' => 'Handle with care',
                'lines' => [
                    ['uid' => '11111111-1111-1111-1111-111111111111', 'product_id' => 11, 'product_hint' => 'Widget A [WA-1]', 'qty' => 3, 'currency' => 'USD', 'incoterm' => '', 'purchase_uom' => 'pcs'],
                    ['uid' => '22222222-2222-2222-2222-222222222222', 'product_id' => 12, 'product_hint' => 'Widget B [WB-1]', 'qty' => 2, 'currency' => 'USD', 'incoterm' => '', 'purchase_uom' => 'pcs'],
                ],
            ],
        ]);

        $context = $util->buildCreateContext($draft, 99);

        $this->assertSame(ProductQuoteDraft::FLOW_MULTI, $context['flow']);
        $this->assertSame(5, $context['payload']['contact_id']);
        $this->assertCount(2, $context['payload']['lines']);
        $this->assertSame(11, $context['payload']['lines'][0]['product_id']);
        $this->assertSame(3.0, $context['payload']['lines'][0]['qty']);
    }

    public function test_build_create_context_returns_single_payload_for_ready_single_flow()
    {
        $util = $this->makeWizardUtil();

        $draft = new ProductQuoteDraft([
            'business_id' => 99,
            'flow' => ProductQuoteDraft::FLOW_SINGLE,
            'payload' => [
                'flow' => 'single',
                'contact' => ['contact_id' => 5, 'hint' => 'Alice Co - Alice'],
                'location' => ['location_id' => 7, 'hint' => 'Main Branch (MB)'],
                'quote_date' => '2026-03-18',
                'expires_at' => '2026-03-25',
                'shipment_port' => '',
                'remark' => null,
                'lines' => [
                    ['uid' => '33333333-3333-3333-3333-333333333333', 'product_id' => 11, 'product_hint' => 'Widget A [WA-1]', 'qty' => 1, 'currency' => 'USD', 'incoterm' => '', 'purchase_uom' => 'pcs'],
                ],
            ],
        ]);

        $context = $util->buildCreateContext($draft, 99);

        $this->assertSame(ProductQuoteDraft::FLOW_SINGLE, $context['flow']);
        $this->assertSame(11, $context['product_id']);
        $this->assertSame(5, $context['payload']['contact_id']);
        $this->assertSame(1.0, $context['payload']['qty']);
        $this->assertSame('USD', $context['payload']['currency']);
    }

    public function test_confirm_draft_rejects_non_ready_status(): void
    {
        $util = $this->makeWizardUtil();
        $draft = new ProductQuoteDraft([
            'id' => '55555555-5555-5555-5555-555555555555',
            'business_id' => 99,
            'user_id' => 1,
            'status' => ProductQuoteDraft::STATUS_CONSUMED,
            'payload' => [],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('aichat::lang.quote_assistant_draft_not_ready'));

        $util->confirmDraft($draft, 99, 1);
    }

    public function test_normalize_line_delta_pricing_converts_total_price_into_unit_price(): void
    {
        $util = $this->makeWizardUtil();
        $method = new \ReflectionMethod(ChatProductQuoteWizardUtil::class, 'normalizeLineDeltaPricing');
        $method->setAccessible(true);

        $normalized = $method->invoke(
            $util,
            ['qty' => 700, 'line_total_price' => 3700, 'price_mode' => 'total'],
            'line total 3700 for qty 700',
            []
        );

        $this->assertEqualsWithDelta(5.2857, (float) ($normalized['base_mill_price'] ?? 0), 0.0001);
        $this->assertArrayNotHasKey('line_total_price', $normalized);
        $this->assertArrayNotHasKey('price_mode', $normalized);
    }

    public function test_normalize_line_delta_pricing_keeps_unit_price_when_total_not_indicated(): void
    {
        $util = $this->makeWizardUtil();
        $method = new \ReflectionMethod(ChatProductQuoteWizardUtil::class, 'normalizeLineDeltaPricing');
        $method->setAccessible(true);

        $normalized = $method->invoke(
            $util,
            ['qty' => 700, 'base_mill_price' => 3700],
            'price 3700 quantity 700',
            []
        );

        $this->assertSame(3700.0, (float) ($normalized['base_mill_price'] ?? 0));
        $this->assertArrayNotHasKey('line_total_price', $normalized);
        $this->assertArrayNotHasKey('price_mode', $normalized);
    }

    public function test_merge_delta_updates_existing_incomplete_line_instead_of_appending_new_line(): void
    {
        $util = $this->makeWizardUtil();
        $method = new \ReflectionMethod(ChatProductQuoteWizardUtil::class, 'mergeDeltaIntoPayload');
        $method->setAccessible(true);

        $payload = [
            'flow' => 'multi',
            'contact' => ['contact_id' => null, 'hint' => null],
            'location' => ['location_id' => null, 'hint' => null],
            'quote_date' => '2026-03-18',
            'expires_at' => '2026-04-01',
            'shipment_port' => '',
            'remark' => null,
            'lines' => [
                [
                    'uid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
                    'product_id' => null,
                    'product_hint' => null,
                    'qty' => 240,
                    'currency' => 'VND',
                    'incoterm' => '',
                    'purchase_uom' => null,
                    'base_mill_price' => null,
                    'test_cost' => null,
                    'surcharge' => null,
                    'finish_uplift_pct' => null,
                    'waste_pct' => null,
                ],
            ],
        ];

        $delta = [
            'lines' => [
                [
                    'product_hint' => '100-Supima-cotton-Single [supima-2142223]',
                    'qty' => 240,
                    'currency' => 'VND',
                ],
            ],
        ];

        $merged = $method->invoke($util, $payload, $delta, 'line 1 product 100-Supima-cotton-Single qty 240');

        $this->assertCount(1, $merged['lines']);
        $this->assertSame('100-Supima-cotton-Single [supima-2142223]', $merged['lines'][0]['product_hint']);
        $this->assertSame(240.0, (float) ($merged['lines'][0]['qty'] ?? 0));
        $this->assertSame('VND', (string) ($merged['lines'][0]['currency'] ?? ''));
    }

    public function test_merge_delta_honors_explicit_line_number_when_updating_existing_line(): void
    {
        $util = $this->makeWizardUtil();
        $method = new \ReflectionMethod(ChatProductQuoteWizardUtil::class, 'mergeDeltaIntoPayload');
        $method->setAccessible(true);

        $payload = [
            'flow' => 'multi',
            'contact' => ['contact_id' => null, 'hint' => null],
            'location' => ['location_id' => null, 'hint' => null],
            'quote_date' => '2026-03-18',
            'expires_at' => '2026-04-01',
            'shipment_port' => '',
            'remark' => null,
            'lines' => [
                [
                    'uid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
                    'product_id' => null,
                    'product_hint' => 'Legacy Product',
                    'qty' => 120,
                    'currency' => 'VND',
                    'incoterm' => '',
                    'purchase_uom' => null,
                    'base_mill_price' => null,
                    'test_cost' => null,
                    'surcharge' => null,
                    'finish_uplift_pct' => null,
                    'waste_pct' => null,
                ],
                [
                    'uid' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
                    'product_id' => null,
                    'product_hint' => null,
                    'qty' => null,
                    'currency' => null,
                    'incoterm' => '',
                    'purchase_uom' => null,
                    'base_mill_price' => null,
                    'test_cost' => null,
                    'surcharge' => null,
                    'finish_uplift_pct' => null,
                    'waste_pct' => null,
                ],
            ],
        ];

        $delta = [
            'lines' => [
                [
                    'line_index' => 2,
                    'product_hint' => 'Widget B [WB-1]',
                    'qty' => 80,
                    'currency' => 'VND',
                ],
            ],
        ];

        $merged = $method->invoke($util, $payload, $delta, 'line 2 product Widget B qty 80');

        $this->assertCount(2, $merged['lines']);
        $this->assertSame('Legacy Product', $merged['lines'][0]['product_hint']);
        $this->assertSame('Widget B [WB-1]', $merged['lines'][1]['product_hint']);
        $this->assertSame(80.0, (float) ($merged['lines'][1]['qty'] ?? 0));
    }

    public function test_process_step_ready_message_contains_confirm_cta_and_never_refusal_disclaimer()
    {
        $capturedAssistantText = '';
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('appendMessage')
            ->once()
            ->withArgs(function ($conversation, $role, $content, $provider, $model, $userId) use (&$capturedAssistantText) {
                $capturedAssistantText = (string) $content;

                return $conversation instanceof ChatConversation
                    && $role === ChatMessage::ROLE_ASSISTANT
                    && $provider === null
                    && $model === null
                    && $userId === 1;
            })
            ->andReturn(new ChatMessage([
                'id' => 321,
                'role' => ChatMessage::ROLE_ASSISTANT,
                'content' => 'ready message',
                'created_at' => now(),
            ]));

        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $aiChatUtil->shouldNotReceive('generateText');

        $util = new ChatProductQuoteWizardUtil(
            $chatUtil,
            $aiChatUtil,
            $this->app->make(ProductCostingUtil::class),
            \Mockery::mock(QuoteUtil::class)
        );

        $conversationId = '00000000-0000-0000-0000-000000000777';
        $draft = ProductQuoteDraft::create([
            'business_id' => 99,
            'user_id' => 1,
            'conversation_id' => $conversationId,
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
            'payload' => [
                'flow' => 'multi',
                'contact' => ['contact_id' => 5, 'hint' => 'Alice Co - Alice'],
                'location' => ['location_id' => 7, 'hint' => 'Main Branch (MB)'],
                'quote_date' => '2026-03-18',
                'expires_at' => '2026-03-25',
                'shipment_port' => '',
                'remark' => null,
                'lines' => [
                    ['uid' => '44444444-4444-4444-4444-444444444444', 'product_id' => 11, 'product_hint' => 'Widget A [WA-1]', 'qty' => 3, 'currency' => 'USD', 'incoterm' => 'FOB', 'purchase_uom' => 'pcs'],
                ],
            ],
        ]);

        $conversation = new ChatConversation([
            'id' => $conversationId,
            'business_id' => 99,
            'user_id' => 1,
        ]);

        $result = $util->processStep($draft, $conversation, 1, 99, ['message' => '']);

        $this->assertSame(ProductQuoteDraft::STATUS_READY, $result['state']['status']);
        $this->assertInstanceOf(ChatMessage::class, $result['assistant_message']);
        $this->assertStringContainsString('Customer: Alice Co - Alice', $capturedAssistantText);
        $this->assertStringContainsString('Location: Main Branch (MB)', $capturedAssistantText);
        $this->assertStringContainsString('Expires: 2026-03-25', $capturedAssistantText);
        $this->assertStringContainsString('Line 1: Widget A [WA-1] | qty 3 | USD | FOB', $capturedAssistantText);
        $this->assertStringContainsString('Use the Confirm button to create this quote in UPOS.', $capturedAssistantText);
        $this->assertStringContainsString('After creation, you will receive links to the public quote and admin page.', $capturedAssistantText);
        $this->assertStringNotContainsStringIgnoringCase('cannot create', $capturedAssistantText);
        $this->assertStringNotContainsStringIgnoringCase('cannot save', $capturedAssistantText);
        $this->assertStringNotContainsStringIgnoringCase('cannot modify', $capturedAssistantText);
    }

    public function test_process_step_remove_line_command_removes_target_line_without_llm_call(): void
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $messageId = 1;
        $chatUtil->shouldReceive('appendMessage')
            ->twice()
            ->andReturnUsing(function ($conversation, $role, $content, $provider, $model, $userId) use (&$messageId) {
                return new ChatMessage([
                    'id' => $messageId++,
                    'role' => $role,
                    'content' => (string) $content,
                    'created_at' => now(),
                ]);
            });

        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $aiChatUtil->shouldNotReceive('generateText');

        $util = new ChatProductQuoteWizardUtil(
            $chatUtil,
            $aiChatUtil,
            $this->app->make(ProductCostingUtil::class),
            \Mockery::mock(QuoteUtil::class)
        );

        $conversationId = '00000000-0000-0000-0000-000000009999';
        $draft = ProductQuoteDraft::create([
            'business_id' => 99,
            'user_id' => 1,
            'conversation_id' => $conversationId,
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
            'payload' => [
                'flow' => 'multi',
                'contact' => ['contact_id' => 5, 'hint' => 'Alice Co - Alice'],
                'location' => ['location_id' => 7, 'hint' => 'Main Branch (MB)'],
                'quote_date' => '2026-03-18',
                'expires_at' => '2026-03-25',
                'shipment_port' => '',
                'remark' => null,
                'lines' => [
                    ['uid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'product_id' => 11, 'product_hint' => 'Widget A [WA-1]', 'qty' => 3, 'currency' => 'USD', 'incoterm' => '', 'purchase_uom' => 'pcs'],
                    ['uid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'product_id' => 12, 'product_hint' => 'Widget B [WB-1]', 'qty' => 2, 'currency' => 'USD', 'incoterm' => '', 'purchase_uom' => 'pcs'],
                ],
            ],
        ]);

        $conversation = new ChatConversation([
            'id' => $conversationId,
            'business_id' => 99,
            'user_id' => 1,
        ]);

        $result = $util->processStep($draft, $conversation, 1, 99, ['message' => 'remove line 2']);
        $lines = (array) data_get($result, 'draft.payload.lines', []);

        $this->assertCount(1, $lines);
        $this->assertSame('Widget A [WA-1]', (string) ($lines[0]['product_hint'] ?? ''));
    }

    protected function makeWizardUtil(): ChatProductQuoteWizardUtil
    {
        return new ChatProductQuoteWizardUtil(
            \Mockery::mock(ChatUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $this->app->make(ProductCostingUtil::class),
            \Mockery::mock(QuoteUtil::class)
        );
    }
}
