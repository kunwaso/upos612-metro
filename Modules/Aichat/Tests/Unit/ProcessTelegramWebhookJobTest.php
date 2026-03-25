<?php

namespace Modules\Aichat\Tests\Unit;

use App\User;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ChatPendingAction;
use Modules\Aichat\Entities\ChatSetting;
use Modules\Aichat\Entities\ProductQuoteDraft;
use Modules\Aichat\Entities\TelegramBot;
use Modules\Aichat\Entities\TelegramChat;
use Modules\Aichat\Jobs\ProcessTelegramWebhookJob;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatActionUtil;
use Modules\Aichat\Utils\ChatProductQuoteWizardUtil;
use Modules\Aichat\Utils\ChatUtil;
use Modules\Aichat\Utils\ChatWorkflowUtil;
use Modules\Aichat\Utils\TelegramApiUtil;
use Tests\TestCase;

class ProcessTelegramWebhookJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aichat.actions.enabled', true);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('aichat:telegram:chat:44:777');
        RateLimiter::clear('aichat:telegram:business:44');
        \Mockery::close();

        parent::tearDown();
    }

    public function test_quote_command_starts_telegram_wizard_and_sends_assistant_prompt(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/quote',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
            'product_quote.create' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000111', 'business_id' => 44, 'user_id' => 5]);
        $draft = new ProductQuoteDraft([
            'id' => '00000000-0000-0000-0000-000000000001',
            'business_id' => 44,
            'user_id' => 5,
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
        ]);
        $assistantMessage = new ChatMessage([
            'content' => 'Who is this quote for?',
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('audit')->once();
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getOrCreateDraft')->once()->with(null, 5, 44, null, 777)->andReturn($draft);
        $quoteWizardUtil->shouldReceive('processStep')->once()->with(
            $draft,
            $conversation,
            5,
            44,
            \Mockery::on(function ($input) {
                return ($input['channel'] ?? null) === 'telegram' && ($input['message'] ?? null) === '';
            })
        )->andReturn([
            'draft' => $draft,
            'assistant_message' => $assistantMessage,
            'state' => ['status' => ProductQuoteDraft::STATUS_COLLECTING],
        ]);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertSame('Who is this quote for?', $sentMessage);
    }

    public function test_confirm_command_creates_quote_for_active_telegram_wizard_draft(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => 'CONFIRM',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
            'product_quote.create' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000222', 'business_id' => 44, 'user_id' => 5]);
        $draft = new ProductQuoteDraft([
            'id' => '00000000-0000-0000-0000-000000000002',
            'business_id' => 44,
            'user_id' => 5,
            'status' => ProductQuoteDraft::STATUS_READY,
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('appendMessage')->once()->with(
            $conversation,
            ChatMessage::ROLE_ASSISTANT,
            \Mockery::type('string'),
            null,
            null,
            5
        );
        $chatUtil->shouldReceive('audit')->once();
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn($draft);
        $quoteWizardUtil->shouldReceive('confirmDraft')->once()->with($draft, 44, 5)->andReturn([
            'quote' => (object) ['id' => 7001],
            'draft' => (object) ['id' => '00000000-0000-0000-0000-000000000002'],
            'public_url' => 'https://example.com/public-quote',
            'admin_url' => 'https://example.com/admin-quote',
        ]);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function (string $message) use (&$sentMessage) {
                $sentMessage = $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertNotNull($sentMessage);
        $this->assertStringContainsString('https://example.com/public-quote', $sentMessage);
        $this->assertStringContainsString('https://example.com/admin-quote', $sentMessage);
    }

    public function test_cancel_command_returns_no_active_draft_message_when_none_exists(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/cancel',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertSame(__('aichat::lang.telegram_quote_wizard_no_active_draft'), $sentMessage);
    }

    public function test_cancel_command_expires_active_draft_and_sends_confirmation(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/cancel',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $draft = new ProductQuoteDraft([
            'id' => '00000000-0000-0000-0000-000000000007',
            'business_id' => 44,
            'user_id' => 5,
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('audit')->once();
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn($draft);
        $quoteWizardUtil->shouldReceive('expireDraft')->once()->with($draft)->andReturn($draft);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertSame(__('aichat::lang.telegram_quote_wizard_cancelled'), $sentMessage);
    }

    public function test_customer_pick_command_maps_index_to_contact_id_and_processes_wizard_step(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => 'C 2',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
            'product_quote.create' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000333', 'business_id' => 44, 'user_id' => 5]);
        $draft = new ProductQuoteDraft([
            'id' => '00000000-0000-0000-0000-000000000003',
            'business_id' => 44,
            'user_id' => 5,
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
        ]);
        $assistantMessage = new ChatMessage([
            'content' => 'Customer selected. Please continue.',
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('audit')->once();
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn($draft);
        $quoteWizardUtil->shouldReceive('serializeDraft')->once()->with($draft)->andReturn([
            'pick_lists' => [
                'contacts' => [
                    ['id' => 11, 'label' => 'Customer A'],
                    ['id' => 22, 'label' => 'Customer B'],
                ],
            ],
        ]);
        $quoteWizardUtil->shouldReceive('processStep')->once()->with(
            $draft,
            $conversation,
            5,
            44,
            \Mockery::on(function ($input) {
                return ($input['channel'] ?? null) === 'telegram'
                    && ($input['message'] ?? null) === ''
                    && (int) ($input['selected_contact_id'] ?? 0) === 22;
            })
        )->andReturn([
            'draft' => $draft,
            'assistant_message' => $assistantMessage,
            'state' => ['status' => ProductQuoteDraft::STATUS_COLLECTING],
        ]);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertSame('Customer selected. Please continue.', $sentMessage);
    }

    public function test_product_pick_command_maps_indexes_to_product_and_line_uid(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => 'P 1 2',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
            'product_quote.create' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000444', 'business_id' => 44, 'user_id' => 5]);
        $draft = new ProductQuoteDraft([
            'id' => '00000000-0000-0000-0000-000000000004',
            'business_id' => 44,
            'user_id' => 5,
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
        ]);
        $assistantMessage = new ChatMessage([
            'content' => 'Product selected. Please continue.',
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('audit')->once();
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn($draft);
        $quoteWizardUtil->shouldReceive('serializeDraft')->once()->with($draft)->andReturn([
            'pick_lists' => [
                'products' => [
                    [
                        'line_uid' => 'line-uid-1',
                        'label' => 'Line 1',
                        'options' => [
                            ['id' => 111, 'label' => 'Product One'],
                            ['id' => 222, 'label' => 'Product Two'],
                        ],
                    ],
                ],
            ],
        ]);
        $quoteWizardUtil->shouldReceive('processStep')->once()->with(
            $draft,
            $conversation,
            5,
            44,
            \Mockery::on(function ($input) {
                return ($input['channel'] ?? null) === 'telegram'
                    && ($input['message'] ?? null) === ''
                    && (int) ($input['selected_product_id'] ?? 0) === 222
                    && (string) ($input['selected_line_uid'] ?? '') === 'line-uid-1';
            })
        )->andReturn([
            'draft' => $draft,
            'assistant_message' => $assistantMessage,
            'state' => ['status' => ProductQuoteDraft::STATUS_COLLECTING],
        ]);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertSame('Product selected. Please continue.', $sentMessage);
    }

    public function test_quote_command_denies_user_without_wizard_permission(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/quote',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => false,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldNotReceive('getOrCreateDraft');
        $quoteWizardUtil->shouldNotReceive('processStep');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertSame(__('messages.unauthorized_action'), $sentMessage);
    }

    public function test_non_wizard_private_messages_fall_back_to_generic_telegram_chat_flow(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => 'hello from telegram',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
            'product_quote.create' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000555', 'business_id' => 44, 'user_id' => 5]);
        $settings = new ChatSetting([
            'chat_markdown' => false,
            'response_max_chars' => null,
        ]);
        $credential = (object) ['encrypted_api_key' => 'encrypted-ai-key'];
        $assistantMarkdown = "1. **Product A**\n* **SKU:** PE-Roll-38664";
        $assistantTelegramText = "1. Product A\n- SKU: PE-Roll-38664";

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('buildModelOptions')->once()->with(44, 5)->andReturn([
            'default_provider' => 'openai',
            'default_model' => 'gpt-4o-mini',
        ]);
        $chatUtil->shouldReceive('decryptApiKey')->once()->with('encrypted-ai-key')->andReturn('decrypted-ai-key');
        $chatUtil->shouldReceive('appendMessage')
            ->once()
            ->with(
                $conversation,
                ChatMessage::ROLE_ASSISTANT,
                $assistantMarkdown,
                'openai',
                'gpt-4o-mini',
                5
            );
        $chatUtil->shouldReceive('normalizeTelegramOutboundText')
            ->once()
            ->with($assistantMarkdown)
            ->andReturn($assistantTelegramText);
        $chatUtil->shouldReceive('getDecryptedBotToken')->twice()->with($bot)->andReturn('telegram-bot-token');

        $chatWorkflowUtil = \Mockery::mock(ChatWorkflowUtil::class);
        $chatWorkflowUtil->shouldReceive('prepareSendOrStreamContext')
            ->once()
            ->andReturn([
                'success' => true,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'settings' => $settings,
                'credential' => $credential,
                'messages' => [
                    ['role' => 'user', 'content' => 'hello from telegram'],
                ],
            ]);
        $chatWorkflowUtil->shouldReceive('normalizeAssistantText')
            ->once()
            ->with($assistantMarkdown, $settings)
            ->andReturn(['text' => $assistantMarkdown]);

        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $aiChatUtil->shouldReceive('generateText')
            ->once()
            ->with('openai', 'decrypted-ai-key', 'gpt-4o-mini', \Mockery::type('array'))
            ->andReturn($assistantMarkdown);

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendChatAction')->once()->with('telegram-bot-token', 777, 'typing');
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            $chatWorkflowUtil,
            $aiChatUtil,
            $telegramApi,
            $quoteWizardUtil
        );

        $this->assertSame($assistantTelegramText, $sentMessage);
    }

    public function test_actions_command_lists_pending_actions_for_private_chat(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/actions',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000666', 'business_id' => 44, 'user_id' => 5]);
        $conversation->id = '00000000-0000-0000-0000-000000000666';

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('buildModelOptions');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('listPendingActions')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000666')
            ->andReturn([
                [
                    'id' => 91,
                    'module' => 'products',
                    'action' => 'update',
                    'preview_text' => 'Update product #15',
                ],
            ]);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertStringContainsString(__('aichat::lang.telegram_action_pending_header'), (string) $sentMessage);
        $this->assertStringContainsString('#91 products.update - Update product #15', (string) $sentMessage);
    }

    public function test_confirm_action_command_confirms_latest_pending_action_when_no_id_is_provided(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/confirm_action',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000777', 'business_id' => 44, 'user_id' => 5]);
        $conversation->id = '00000000-0000-0000-0000-000000000777';
        $pendingAction = new ChatPendingAction(['id' => 88, 'module' => 'products', 'action' => 'update']);
        $pendingAction->id = 88;
        $executedAction = new ChatPendingAction(['id' => 88, 'module' => 'products', 'action' => 'update']);
        $executedAction->id = 88;

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('buildModelOptions');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('getLatestPendingActionForUser')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000777')
            ->andReturn($pendingAction);
        $chatActionUtil->shouldReceive('confirmAction')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000777', 88, 'telegram', null)
            ->andReturn($executedAction);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertStringContainsString(__('aichat::lang.telegram_action_confirm_success'), (string) $sentMessage);
        $this->assertStringContainsString('#88 products.update', (string) $sentMessage);
    }

    public function test_cancel_action_command_cancels_latest_pending_action_when_no_id_is_provided(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/cancel_action',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000778', 'business_id' => 44, 'user_id' => 5]);
        $conversation->id = '00000000-0000-0000-0000-000000000778';
        $pendingAction = new ChatPendingAction(['id' => 89, 'module' => 'products', 'action' => 'delete']);
        $pendingAction->id = 89;
        $cancelledAction = new ChatPendingAction(['id' => 89, 'module' => 'products', 'action' => 'delete']);
        $cancelledAction->id = 89;

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('buildModelOptions');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('getLatestPendingActionForUser')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000778')
            ->andReturn($pendingAction);
        $chatActionUtil->shouldReceive('cancelAction')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000778', 89, null)
            ->andReturn($cancelledAction);
        $chatActionUtil->shouldNotReceive('confirmAction');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertStringContainsString(__('aichat::lang.telegram_action_cancel_success'), (string) $sentMessage);
        $this->assertStringContainsString('#89 products.delete', (string) $sentMessage);
    }

    public function test_confirm_action_command_confirms_specified_pending_action_by_id(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/confirm_action 91',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000779', 'business_id' => 44, 'user_id' => 5]);
        $conversation->id = '00000000-0000-0000-0000-000000000779';
        $pendingAction = new ChatPendingAction(['id' => 91, 'module' => 'sales', 'action' => 'update']);
        $pendingAction->id = 91;
        $executedAction = new ChatPendingAction(['id' => 91, 'module' => 'sales', 'action' => 'update']);
        $executedAction->id = 91;

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('buildModelOptions');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('getPendingActionByIdForUser')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000779', 91)
            ->andReturn($pendingAction);
        $chatActionUtil->shouldReceive('confirmAction')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000779', 91, 'telegram', null)
            ->andReturn($executedAction);
        $chatActionUtil->shouldNotReceive('cancelAction');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertStringContainsString(__('aichat::lang.telegram_action_confirm_success'), (string) $sentMessage);
        $this->assertStringContainsString('#91 sales.update', (string) $sentMessage);
    }

    public function test_cancel_action_command_cancels_specified_pending_action_by_id(): void
    {
        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/cancel_action 92',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000780', 'business_id' => 44, 'user_id' => 5]);
        $conversation->id = '00000000-0000-0000-0000-000000000780';
        $pendingAction = new ChatPendingAction(['id' => 92, 'module' => 'purchases', 'action' => 'delete']);
        $pendingAction->id = 92;
        $cancelledAction = new ChatPendingAction(['id' => 92, 'module' => 'purchases', 'action' => 'delete']);
        $cancelledAction->id = 92;

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('buildModelOptions');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('getPendingActionByIdForUser')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000780', 92)
            ->andReturn($pendingAction);
        $chatActionUtil->shouldReceive('cancelAction')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000780', 92, null)
            ->andReturn($cancelledAction);
        $chatActionUtil->shouldNotReceive('confirmAction');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertStringContainsString(__('aichat::lang.telegram_action_cancel_success'), (string) $sentMessage);
        $this->assertStringContainsString('#92 purchases.delete', (string) $sentMessage);
    }

    public function test_invalid_pending_action_id_returns_safe_message_without_crashing(): void
    {
        config()->set('aichat.actions.enabled', true);

        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/confirm_action 999',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000781', 'business_id' => 44, 'user_id' => 5]);
        $conversation->id = '00000000-0000-0000-0000-000000000781';

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('buildModelOptions');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('getPendingActionByIdForUser')
            ->once()
            ->with(44, 5, '00000000-0000-0000-0000-000000000781', 999)
            ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());
        $chatActionUtil->shouldNotReceive('confirmAction');
        $chatActionUtil->shouldNotReceive('cancelAction');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertSame(__('aichat::lang.telegram_action_failed'), $sentMessage);
    }

    public function test_actions_command_returns_disabled_message_when_feature_flag_is_off(): void
    {
        config()->set('aichat.actions.enabled', false);

        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/actions',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('getOrCreateTelegramConversation');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('listPendingActions');
        $chatActionUtil->shouldNotReceive('confirmAction');
        $chatActionUtil->shouldNotReceive('cancelAction');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertSame(__('aichat::lang.chat_action_disabled'), $sentMessage);
    }

    public function test_confirm_action_command_returns_disabled_message_when_feature_flag_is_off(): void
    {
        config()->set('aichat.actions.enabled', false);

        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/confirm_action 91',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('getOrCreateTelegramConversation');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('confirmAction');
        $chatActionUtil->shouldNotReceive('cancelAction');
        $chatActionUtil->shouldNotReceive('listPendingActions');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertSame(__('aichat::lang.chat_action_disabled'), $sentMessage);
    }

    public function test_cancel_action_command_returns_disabled_message_when_feature_flag_is_off(): void
    {
        config()->set('aichat.actions.enabled', false);

        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/cancel_action 91',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');
        $chatUtil->shouldNotReceive('getOrCreateTelegramConversation');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn(null);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('confirmAction');
        $chatActionUtil->shouldNotReceive('cancelAction');
        $chatActionUtil->shouldNotReceive('listPendingActions');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertSame(__('aichat::lang.chat_action_disabled'), $sentMessage);
    }

    public function test_pending_action_commands_do_not_override_active_quote_wizard_flow(): void
    {
        config()->set('aichat.actions.enabled', true);

        $sentMessage = null;
        $job = new TestableProcessTelegramWebhookJob('hook-key', [
            'message' => [
                'chat' => ['id' => 777, 'type' => 'private'],
                'text' => '/confirm_action 91',
            ],
        ]);
        $job->setResolvedUser($this->makeTelegramUser(5, [
            'aichat.quote_wizard.use' => true,
            'product_quote.create' => true,
        ]));

        $bot = new TelegramBot(['business_id' => 44, 'linked_user_id' => 1, 'encrypted_bot_token' => 'encrypted-token']);
        $telegramChat = new TelegramChat(['business_id' => 44, 'telegram_chat_id' => 777, 'user_id' => 5]);
        $conversation = new ChatConversation(['id' => '00000000-0000-0000-0000-000000000888', 'business_id' => 44, 'user_id' => 5]);
        $conversation->id = '00000000-0000-0000-0000-000000000888';
        $draft = new ProductQuoteDraft([
            'id' => '00000000-0000-0000-0000-000000000008',
            'business_id' => 44,
            'user_id' => 5,
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
        ]);
        $assistantMessage = new ChatMessage([
            'content' => 'Wizard still active.',
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('findTelegramBotByWebhookKey')->once()->with('hook-key')->andReturn($bot);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('getTelegramChatForBusiness')->once()->with(44, 777)->andReturn($telegramChat);
        $chatUtil->shouldReceive('isUserAllowedForTelegram')->once()->with(44, 5)->andReturn(true);
        $chatUtil->shouldReceive('getOrCreateTelegramConversation')->once()->with(44, 777, 5)->andReturn($conversation);
        $chatUtil->shouldReceive('audit')->once();
        $chatUtil->shouldReceive('getDecryptedBotToken')->once()->with($bot)->andReturn('telegram-bot-token');

        $quoteWizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $quoteWizardUtil->shouldReceive('getLatestActiveDraftForChannel')->once()->with(44, 5, null, 777)->andReturn($draft);
        $quoteWizardUtil->shouldReceive('serializeDraft')->once()->with($draft)->andReturn([]);
        $quoteWizardUtil->shouldReceive('processStep')->once()->with(
            $draft,
            $conversation,
            5,
            44,
            \Mockery::on(function ($input) {
                return ($input['channel'] ?? null) === 'telegram'
                    && ($input['message'] ?? null) === '/confirm_action 91';
            })
        )->andReturn([
            'draft' => $draft,
            'assistant_message' => $assistantMessage,
            'state' => ['status' => ProductQuoteDraft::STATUS_COLLECTING],
        ]);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('listPendingActions');
        $chatActionUtil->shouldNotReceive('confirmAction');
        $chatActionUtil->shouldNotReceive('cancelAction');

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('sendMessage')->once()->with(
            'telegram-bot-token',
            777,
            \Mockery::on(function ($message) use (&$sentMessage) {
                $sentMessage = (string) $message;

                return true;
            })
        );

        $job->handle(
            $chatUtil,
            \Mockery::mock(ChatWorkflowUtil::class),
            \Mockery::mock(AIChatUtil::class),
            $telegramApi,
            $quoteWizardUtil,
            $chatActionUtil
        );

        $this->assertSame('Wizard still active.', $sentMessage);
    }

    protected function makeTelegramUser(int $id, array $abilities): User
    {
        return new class($id, $abilities) extends User
        {
            protected int $testUserId;

            protected array $abilities = [];

            public function __construct(int $id, array $abilities)
            {
                parent::__construct();
                $this->testUserId = $id;
                $this->id = $id;
                $this->abilities = $abilities;
            }

            public function can($ability, $arguments = [])
            {
                return (bool) ($this->abilities[$ability] ?? false);
            }
        };
    }
}

class TestableProcessTelegramWebhookJob extends ProcessTelegramWebhookJob
{
    protected ?User $resolvedUser = null;

    public function setResolvedUser(User $user): void
    {
        $this->resolvedUser = $user;
    }

    protected function resolveTelegramUser(int $businessId, int $userId): ?User
    {
        return $this->resolvedUser;
    }
}
