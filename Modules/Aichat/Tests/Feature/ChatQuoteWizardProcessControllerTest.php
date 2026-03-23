<?php

namespace Modules\Aichat\Tests\Feature;

use App\User;
use App\Http\Middleware\AdminSidebarMenu;
use App\Http\Middleware\Language;
use App\Http\Middleware\SetSessionData;
use App\Http\Middleware\Timezone;
use App\Http\Middleware\VerifyCsrfToken;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ProductQuoteDraft;
use Modules\Aichat\Utils\ChatProductQuoteWizardUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatQuoteWizardProcessControllerTest extends TestCase
{
    public function test_process_returns_ready_summary_with_confirm_cta_in_assistant_message()
    {
        $this->withoutMiddleware([SetSessionData::class, Language::class, Timezone::class, AdminSidebarMenu::class, VerifyCsrfToken::class]);
        $this->actingAs($this->makeUser(true, true));

        $conversationId = '00000000-0000-0000-0000-000000000111';
        $draftId = '00000000-0000-0000-0000-000000000222';
        $assistantText = "Quote draft ready for confirmation:\nCustomer: PEKO\nLocation: Main Branch\nExpires: 2026-03-25\nLines:\n- Line 1: PK-2365844 | qty 2 | USD | FOB\n\nUse the Confirm button to create this quote in UPOS.\nAfter creation, you will receive links to the public quote and admin page.";

        $conversation = \Mockery::mock(ChatConversation::class)->makePartial();
        $conversation->id = $conversationId;
        $conversation->business_id = 44;
        $conversation->user_id = 1;
        $conversation->shouldReceive('fresh')->once()->andReturnSelf();

        $draft = new ProductQuoteDraft([
            'id' => $draftId,
            'business_id' => 44,
            'user_id' => 1,
            'conversation_id' => $conversationId,
            'status' => ProductQuoteDraft::STATUS_READY,
            'flow' => ProductQuoteDraft::FLOW_MULTI,
        ]);

        $assistantMessage = new ChatMessage([
            'id' => 123,
            'business_id' => 44,
            'conversation_id' => $conversationId,
            'user_id' => 1,
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => $assistantText,
            'created_at' => now(),
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getConversationByIdForUser')->once()->with(44, 1, $conversationId)->andReturn($conversation);
        $chatUtil->shouldReceive('audit')->zeroOrMoreTimes();
        $chatUtil->shouldReceive('serializeConversation')->once()->andReturn(['id' => $conversationId]);
        $chatUtil->shouldReceive('serializeMessage')->once()->with($assistantMessage)->andReturn([
            'id' => 123,
            'role' => 'assistant',
            'content' => $assistantText,
        ]);
        $this->app->instance(ChatUtil::class, $chatUtil);

        $wizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $wizardUtil->shouldReceive('getOrCreateDraft')
            ->once()
            ->andReturn($draft);
        $wizardUtil->shouldReceive('processStep')
            ->once()
            ->andReturn([
                'draft' => $draft,
                'user_message' => null,
                'assistant_message' => $assistantMessage,
                'state' => ['status' => ProductQuoteDraft::STATUS_READY],
            ]);
        $wizardUtil->shouldReceive('serializeDraft')->once()->with($draft)->andReturn([
            'id' => $draftId,
            'status' => ProductQuoteDraft::STATUS_READY,
            'summary' => [
                'customer' => 'PEKO',
                'location' => 'Main Branch',
                'expires_at' => '2026-03-25',
                'lines' => [
                    ['label' => 'Line 1', 'text' => 'PK-2365844 | qty 2 | USD | FOB'],
                ],
            ],
            'missing_fields' => [],
            'pick_lists' => [],
            'result' => [],
        ]);
        $this->app->instance(ChatProductQuoteWizardUtil::class, $wizardUtil);

        $response = $this->withSession(['user.business_id' => 44])->post(
            '/aichat/chat/conversations/' . $conversationId . '/quote-wizard/process',
            [
                'message' => 'ready',
                'draft_id' => $draftId,
            ]
        );

        $response->assertOk();
        $payload = $response->json();

        $this->assertTrue((bool) data_get($payload, 'success'));
        $this->assertSame(ProductQuoteDraft::STATUS_READY, data_get($payload, 'data.draft.status'));
        $this->assertSame($assistantText, (string) data_get($payload, 'data.assistant_message.content'));
        $this->assertStringContainsString('Use the Confirm button to create this quote in UPOS.', (string) data_get($payload, 'data.assistant_message.content'));
        $this->assertStringNotContainsStringIgnoringCase('cannot create', (string) data_get($payload, 'data.assistant_message.content'));
        $this->assertStringNotContainsStringIgnoringCase('cannot save', (string) data_get($payload, 'data.assistant_message.content'));
        $this->assertStringNotContainsStringIgnoringCase('cannot modify', (string) data_get($payload, 'data.assistant_message.content'));
    }

    protected function makeUser(bool $canUseQuoteWizard, bool $canViewChat): User
    {
        return new class($canUseQuoteWizard, $canViewChat) extends User
        {
            protected bool $canUseQuoteWizard = false;

            protected bool $canViewChat = false;

            public function __construct(bool $canUseQuoteWizard = false, bool $canViewChat = false)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->canUseQuoteWizard = $canUseQuoteWizard;
                $this->canViewChat = $canViewChat;
            }

            public function hasRole($roles, ?string $guard = null): bool
            {
                return false;
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return $permission === 'aichat.quote_wizard.use'
                    ? $this->canUseQuoteWizard
                    : false;
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->hasPermissionTo($permission, $guardName);
            }

            public function can($ability, $arguments = [])
            {
                if ($ability === 'aichat.quote_wizard.use') {
                    return $this->canUseQuoteWizard;
                }

                if ($ability === 'aichat.chat.view') {
                    return $this->canViewChat;
                }

                return false;
            }
        };
    }
}
