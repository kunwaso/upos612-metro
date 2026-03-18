<?php

namespace Modules\Aichat\Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ProductQuoteDraft;
use Modules\Aichat\Http\Controllers\ChatQuoteWizardController;
use Modules\Aichat\Http\Requests\Chat\ConfirmProductQuoteDraftRequest;
use Modules\Aichat\Utils\ChatProductQuoteWizardUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatQuoteWizardConfirmControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        if (! Schema::hasTable('aichat_product_quote_drafts')) {
            Schema::create('aichat_product_quote_drafts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('user_id');
                $table->uuid('conversation_id')->nullable();
                $table->string('flow')->default(ProductQuoteDraft::FLOW_MULTI);
                $table->string('status')->default(ProductQuoteDraft::STATUS_COLLECTING);
                $table->text('payload')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('consumed_at')->nullable();
                $table->timestamp('last_interaction_at')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('aichat_product_quote_drafts');
        \Mockery::close();

        parent::tearDown();
    }

    public function test_confirm_returns_unauthorized_when_user_lacks_product_quote_create_permission()
    {
        $this->be($this->makeUser(true, false));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(ConfirmProductQuoteDraftRequest::class);
        $request->shouldReceive('session')->andReturn($session);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);

        $controller = new ChatQuoteWizardController(
            $chatUtil,
            \Mockery::mock(ChatProductQuoteWizardUtil::class)
        );

        $response = $controller->confirm($request, '00000000-0000-0000-0000-000000000111');

        $payload = $response->getData(true);
        $this->assertFalse($payload['success']);
    }

    public function test_confirm_returns_links_when_draft_confirmation_succeeds()
    {
        $this->be($this->makeUser(true, true));

        $conversationId = '00000000-0000-0000-0000-000000000111';
        $draftId = '00000000-0000-0000-0000-000000000222';

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(ConfirmProductQuoteDraftRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn(['draft_id' => $draftId]);

        ProductQuoteDraft::query()->forceCreate([
            'id' => $draftId,
            'business_id' => 44,
            'user_id' => 1,
            'conversation_id' => $conversationId,
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'status' => ProductQuoteDraft::STATUS_READY,
            'payload' => ['lines' => []],
            'expires_at' => now()->addDay(),
        ]);
        $this->assertNotNull(ProductQuoteDraft::forBusiness(44)->forUser(1)->forConversation($conversationId)->where('id', $draftId)->first());

        $conversation = \Mockery::mock(ChatConversation::class)->makePartial();
        $conversation->id = $conversationId;
        $conversation->business_id = 44;
        $conversation->user_id = 1;

        $assistantMessage = new ChatMessage([
            'id' => 99,
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => 'Quote created successfully.',
            'created_at' => now(),
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getConversationByIdForUser')->once()->with(44, 1, $conversationId)->andReturn($conversation);
        $chatUtil->shouldReceive('appendMessage')
            ->once()
            ->with(
                $conversation,
                ChatMessage::ROLE_ASSISTANT,
                \Mockery::on(function ($content) {
                    $text = (string) $content;

                    return str_contains($text, 'https://example.com/public-quote')
                        && str_contains($text, 'https://example.com/admin-quote');
                }),
                null,
                null,
                1
            )
            ->andReturn($assistantMessage);
        $chatUtil->shouldReceive('serializeMessage')->once()->with($assistantMessage)->andReturn([
            'id' => 99,
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => 'Quote created successfully.',
        ]);
        $chatUtil->shouldReceive('audit')->once();

        $wizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $wizardUtil->shouldReceive('confirmDraft')->once()->with(
            \Mockery::on(function ($draft) use ($draftId) {
                return $draft instanceof ProductQuoteDraft && (string) $draft->id === $draftId;
            }),
            44,
            1
        )->andReturn([
            'quote' => (object) ['id' => 701],
            'draft' => ProductQuoteDraft::query()->findOrFail($draftId),
            'public_url' => 'https://example.com/public-quote',
            'admin_url' => 'https://example.com/admin-quote',
        ]);
        $wizardUtil->shouldReceive('serializeDraft')->once()->with(
            \Mockery::on(function ($draft) use ($draftId) {
                return $draft instanceof ProductQuoteDraft && (string) $draft->id === $draftId;
            })
        )->andReturn([
            'id' => $draftId,
            'status' => ProductQuoteDraft::STATUS_CONSUMED,
            'result' => [
                'public_url' => 'https://example.com/public-quote',
                'admin_url' => 'https://example.com/admin-quote',
            ],
        ]);

        $controller = new ChatQuoteWizardController($chatUtil, $wizardUtil);
        $response = $controller->confirm($request, $conversationId);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame(701, data_get($payload, 'data.quote_id'));
        $this->assertSame('https://example.com/public-quote', data_get($payload, 'data.public_url'));
        $this->assertSame('https://example.com/admin-quote', data_get($payload, 'data.admin_url'));
    }

    public function test_confirm_returns_422_when_confirm_fails_validation()
    {
        $this->be($this->makeUser(true, true));

        $conversationId = '00000000-0000-0000-0000-000000000333';
        $draftId = '00000000-0000-0000-0000-000000000444';

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(ConfirmProductQuoteDraftRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn(['draft_id' => $draftId]);

        ProductQuoteDraft::query()->forceCreate([
            'id' => $draftId,
            'business_id' => 44,
            'user_id' => 1,
            'conversation_id' => $conversationId,
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'status' => ProductQuoteDraft::STATUS_READY,
            'payload' => ['lines' => []],
            'expires_at' => now()->addDay(),
        ]);
        $this->assertNotNull(ProductQuoteDraft::forBusiness(44)->forUser(1)->forConversation($conversationId)->where('id', $draftId)->first());

        $conversation = \Mockery::mock(ChatConversation::class)->makePartial();
        $conversation->id = $conversationId;
        $conversation->business_id = 44;
        $conversation->user_id = 1;

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getConversationByIdForUser')->once()->with(44, 1, $conversationId)->andReturn($conversation);

        $wizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $wizardUtil->shouldReceive('confirmDraft')->once()->with(
            \Mockery::on(function ($draft) use ($draftId) {
                return $draft instanceof ProductQuoteDraft && (string) $draft->id === $draftId;
            }),
            44,
            1
        )->andThrow(new \RuntimeException('Draft is no longer ready'));

        $controller = new ChatQuoteWizardController($chatUtil, $wizardUtil);
        $response = $controller->confirm($request, $conversationId);

        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('Draft is no longer ready', (string) ($payload['message'] ?? ''));
    }

    public function test_confirm_second_attempt_fails_gracefully_after_first_success()
    {
        $this->be($this->makeUser(true, true));

        $conversationId = '00000000-0000-0000-0000-000000000555';
        $draftId = '00000000-0000-0000-0000-000000000666';

        $session = \Mockery::mock();
        $session->shouldReceive('get')->times(2)->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(ConfirmProductQuoteDraftRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->times(2)->andReturn(['draft_id' => $draftId]);

        ProductQuoteDraft::query()->forceCreate([
            'id' => $draftId,
            'business_id' => 44,
            'user_id' => 1,
            'conversation_id' => $conversationId,
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'status' => ProductQuoteDraft::STATUS_READY,
            'payload' => ['lines' => []],
            'expires_at' => now()->addDay(),
        ]);

        $conversation = \Mockery::mock(ChatConversation::class)->makePartial();
        $conversation->id = $conversationId;
        $conversation->business_id = 44;
        $conversation->user_id = 1;

        $assistantMessage = new ChatMessage([
            'id' => 100,
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => 'Quote created successfully.',
            'created_at' => now(),
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->times(2)->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getConversationByIdForUser')->times(2)->with(44, 1, $conversationId)->andReturn($conversation);
        $chatUtil->shouldReceive('appendMessage')->once()->andReturn($assistantMessage);
        $chatUtil->shouldReceive('serializeMessage')->once()->with($assistantMessage)->andReturn([
            'id' => 100,
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => 'Quote created successfully.',
        ]);
        $chatUtil->shouldReceive('audit')->once();

        $wizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $wizardUtil->shouldReceive('confirmDraft')->once()->with(
            \Mockery::on(function ($draft) use ($draftId) {
                return $draft instanceof ProductQuoteDraft && (string) $draft->id === $draftId;
            }),
            44,
            1
        )->andReturn([
            'quote' => (object) ['id' => 901],
            'draft' => ProductQuoteDraft::query()->findOrFail($draftId),
            'public_url' => 'https://example.com/public-quote',
            'admin_url' => 'https://example.com/admin-quote',
        ]);
        $wizardUtil->shouldReceive('confirmDraft')->once()->andThrow(new \RuntimeException(__('aichat::lang.quote_assistant_draft_not_ready')));
        $wizardUtil->shouldReceive('serializeDraft')->once()->andReturn([
            'id' => $draftId,
            'status' => ProductQuoteDraft::STATUS_CONSUMED,
            'result' => [
                'public_url' => 'https://example.com/public-quote',
                'admin_url' => 'https://example.com/admin-quote',
            ],
        ]);

        $controller = new ChatQuoteWizardController($chatUtil, $wizardUtil);

        $firstResponse = $controller->confirm($request, $conversationId);
        $this->assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $controller->confirm($request, $conversationId);
        $this->assertSame(422, $secondResponse->getStatusCode());
        $secondPayload = $secondResponse->getData(true);
        $this->assertFalse((bool) ($secondPayload['success'] ?? true));
        $this->assertSame(__('aichat::lang.quote_assistant_draft_not_ready'), (string) ($secondPayload['message'] ?? ''));
    }

    protected function makeUser(bool $canUseQuoteWizard, bool $canCreateQuote): User
    {
        return new class($canUseQuoteWizard, $canCreateQuote) extends User
        {
            protected bool $canUseQuoteWizard = false;

            protected bool $canCreateQuote = false;

            public function __construct(bool $canUseQuoteWizard, bool $canCreateQuote)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->canUseQuoteWizard = $canUseQuoteWizard;
                $this->canCreateQuote = $canCreateQuote;
            }

            public function can($ability, $arguments = [])
            {
                if ($ability === 'aichat.quote_wizard.use') {
                    return $this->canUseQuoteWizard;
                }

                if ($ability === 'product_quote.create') {
                    return $this->canCreateQuote;
                }

                return false;
            }
        };
    }
}
