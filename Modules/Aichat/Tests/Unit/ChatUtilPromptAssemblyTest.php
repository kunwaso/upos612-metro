<?php

namespace Modules\Aichat\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatSetting;
use Modules\Aichat\Utils\ChatAuditUtil;
use Modules\Aichat\Utils\ChatMessageRendererUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatUtilPromptAssemblyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('aichat.actions.enabled', false);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('aichat_chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('conversation_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('role', 20);
            $table->text('content')->nullable();
            $table->string('provider', 32)->nullable();
            $table->string('model', 120)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('aichat_chat_messages');
        parent::tearDown();
    }

    public function test_build_provider_messages_orders_sections_for_authenticated_user()
    {
        $chatUtil = $this->makePromptAwareChatUtil(
            'Summarize intent in one sentence before answering.',
            'Org context line',
            '- Display name: Ray',
            '- Org fact: Uses metric pricing',
            '- User fact: Prefers concise answers',
            ''
        );

        $conversation = new ChatConversation([
            'id' => '00000000-0000-0000-0000-000000000001',
            'business_id' => 99,
        ]);

        $messages = $chatUtil->buildProviderMessages(
            $conversation,
            'Business instruction',
            null,
            30,
            null,
            'Hello',
            null,
            null,
            7
        );

        $systemPrompt = (string) $messages[0]['content'];

        $this->assertSectionOrder($systemPrompt, [
            'Reasoning and response rules:',
            'Additional business instruction:',
            'Organization data context:',
            'User profile and context:',
            'Organization memory:',
            'User memory:',
        ]);
        $this->assertStringContainsString('tailor tone and topics for this user', $systemPrompt);
    }

    public function test_build_provider_messages_keeps_org_only_memory_for_non_user_flow()
    {
        $chatUtil = $this->makePromptAwareChatUtil(
            'Summarize intent in one sentence before answering.',
            'Org context line',
            '',
            '',
            '',
            '- Org-only memory fact'
        );

        $conversation = new ChatConversation([
            'id' => '00000000-0000-0000-0000-000000000002',
            'business_id' => 100,
        ]);

        $messages = $chatUtil->buildProviderMessages(
            $conversation,
            'Business instruction',
            null,
            30,
            null,
            'Hello',
            null,
            null,
            null
        );

        $systemPrompt = (string) $messages[0]['content'];

        $this->assertStringContainsString('Persistent business memory:', $systemPrompt);
        $this->assertStringNotContainsString('User profile and context:', $systemPrompt);
        $this->assertStringNotContainsString('User memory:', $systemPrompt);
    }

    public function test_build_provider_messages_includes_action_workflow_guidance_when_actions_enabled()
    {
        config()->set('aichat.actions.enabled', true);

        $chatUtil = $this->makePromptAwareChatUtil(
            'Follow policy strictly.',
            'Org context line',
            '- Display name: Ray',
            '- Org memory',
            '- User memory',
            ''
        );

        $conversation = new ChatConversation([
            'id' => '00000000-0000-0000-0000-000000000003',
            'business_id' => 101,
        ]);

        $messages = $chatUtil->buildProviderMessages(
            $conversation,
            'Business instruction',
            null,
            30,
            null,
            'Create product PK-004',
            null,
            null,
            7
        );

        $systemPrompt = (string) $messages[0]['content'];

        $this->assertStringContainsString('Action execution workflow:', $systemPrompt);
        $this->assertStringContainsString('/action prepare <module> <action> <json payload>', $systemPrompt);
        $this->assertStringContainsString('/action confirm <id>', $systemPrompt);
    }

    protected function makePromptAwareChatUtil(
        string $reasoningRules,
        string $organizationContext,
        string $userProfileContext,
        string $organizationMemoryContext,
        string $userMemoryContext,
        string $legacyMemoryContext
    ): ChatUtil {
        $chatAuditUtil = \Mockery::mock(ChatAuditUtil::class);
        $rendererUtil = \Mockery::mock(ChatMessageRendererUtil::class);

        $chatUtil = \Mockery::mock(ChatUtil::class, [$chatAuditUtil, $rendererUtil])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $settings = new ChatSetting(['reasoning_rules' => $reasoningRules]);

        $chatUtil->shouldReceive('getOrCreateBusinessSettings')->andReturn($settings);
        $chatUtil->shouldReceive('buildOrganizationContext')->andReturn($organizationContext);
        $chatUtil->shouldReceive('buildUserProfileContext')->andReturn($userProfileContext);
        $chatUtil->shouldReceive('buildOrganizationMemoryContext')->andReturn($organizationMemoryContext);
        $chatUtil->shouldReceive('buildUserMemoryContext')->andReturn($userMemoryContext);
        $chatUtil->shouldReceive('buildMemoryContext')->andReturn($legacyMemoryContext);
        $chatUtil->shouldReceive('resolveChatCapabilities')->andReturn([
            'products' => ['view' => true, 'create' => true, 'update' => false, 'delete' => false],
            'contacts' => [
                'customer' => ['view' => true, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
                'supplier' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
            ],
            'sales' => ['view' => true, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
            'purchases' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
            'quotes' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false, 'send' => false],
            'reports' => ['view' => true, 'export' => false],
            'settings' => ['access' => false, 'chat_settings' => false, 'manage_all_memories' => false],
            'chat' => ['edit' => true],
        ]);

        return $chatUtil;
    }

    protected function assertSectionOrder(string $text, array $sections): void
    {
        $previous = -1;

        foreach ($sections as $section) {
            $position = strpos($text, $section);
            $this->assertNotFalse($position, 'Missing expected section: ' . $section);
            $this->assertGreaterThan($previous, $position, 'Section out of order: ' . $section);
            $previous = $position;
        }
    }
}

