<?php

namespace Modules\Aichat\Tests\Unit;

use App\User;
use Modules\Aichat\Entities\ChatSetting;
use Modules\Aichat\Utils\ChatAuditUtil;
use Modules\Aichat\Utils\ChatMessageRendererUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatUtilClientConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function test_build_client_config_includes_capabilities_action_feature_and_routes(): void
    {
        config()->set('aichat.actions.enabled', true);

        $this->be($this->makeUser(7, [
            'aichat.chat.edit' => true,
            'aichat.chat.settings' => true,
            'aichat.quote_wizard.use' => true,
        ]));

        $chatUtil = $this->makeChatUtilWithMocks(
            44,
            7,
            [
                'products' => ['view' => true, 'create' => true, 'update' => false, 'delete' => false],
                'contacts' => [
                    'customer' => ['view' => true, 'view_own' => true, 'create' => false, 'update' => false, 'delete' => false],
                    'supplier' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
                ],
                'sales' => ['view' => true, 'view_own' => false, 'create' => true, 'update' => false, 'delete' => false],
                'purchases' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
                'quotes' => ['view' => true, 'create' => false, 'update' => false, 'delete' => false, 'send' => false],
                'reports' => ['view' => true, 'export' => false],
                'settings' => ['access' => true, 'chat_settings' => true, 'manage_all_memories' => false],
            ]
        );

        $config = $chatUtil->buildClientConfig(44, 7);

        $this->assertTrue((bool) data_get($config, 'features.actions_enabled'));
        $this->assertSame(true, data_get($config, 'capabilities.products.view'));
        $this->assertArrayHasKey('actions_prepare_url_template', (array) data_get($config, 'routes', []));
        $this->assertArrayHasKey('actions_confirm_url_template', (array) data_get($config, 'routes', []));
        $this->assertArrayHasKey('actions_cancel_url_template', (array) data_get($config, 'routes', []));
        $this->assertArrayHasKey('actions_pending_url_template', (array) data_get($config, 'routes', []));

        $this->assertStringContainsString('__CONVERSATION_ID__', (string) data_get($config, 'routes.actions_prepare_url_template', ''));
        $this->assertStringContainsString('__ACTION_ID__', (string) data_get($config, 'routes.actions_confirm_url_template', ''));
        $this->assertStringContainsString('__ACTION_ID__', (string) data_get($config, 'routes.actions_cancel_url_template', ''));
    }

    public function test_build_client_config_reflects_disabled_actions_feature_flag(): void
    {
        config()->set('aichat.actions.enabled', false);

        $this->be($this->makeUser(7, [
            'aichat.chat.edit' => true,
            'aichat.chat.settings' => false,
            'aichat.quote_wizard.use' => false,
        ]));

        $chatUtil = $this->makeChatUtilWithMocks(
            44,
            7,
            [
                'products' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
                'contacts' => [
                    'customer' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
                    'supplier' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
                ],
                'sales' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
                'purchases' => ['view' => false, 'view_own' => false, 'create' => false, 'update' => false, 'delete' => false],
                'quotes' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'send' => false],
                'reports' => ['view' => false, 'export' => false],
                'settings' => ['access' => false, 'chat_settings' => false, 'manage_all_memories' => false],
            ]
        );

        $config = $chatUtil->buildClientConfig(44, 7);

        $this->assertFalse((bool) data_get($config, 'features.actions_enabled', true));
    }

    protected function makeChatUtilWithMocks(int $businessId, int $userId, array $capabilities): ChatUtil
    {
        $chatAuditUtil = \Mockery::mock(ChatAuditUtil::class);
        $rendererUtil = \Mockery::mock(ChatMessageRendererUtil::class);

        $chatUtil = \Mockery::mock(ChatUtil::class, [$chatAuditUtil, $rendererUtil])
            ->makePartial();

        $settings = new ChatSetting([
            'share_ttl_hours' => 168,
            'default_provider' => 'openai',
            'default_model' => 'gpt-4o-mini',
        ]);

        $chatUtil->shouldReceive('getOrCreateBusinessSettings')
            ->times(2)
            ->with($businessId)
            ->andReturn($settings);

        $chatUtil->shouldReceive('buildModelOptions')
            ->once()
            ->with($businessId, $userId)
            ->andReturn([
                'enabled_providers' => ['openai'],
                'model_options' => [
                    [
                        'provider' => 'openai',
                        'model_id' => 'gpt-4o-mini',
                        'label' => 'GPT-4o mini (OpenAI)',
                    ],
                ],
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o-mini',
            ]);

        $chatUtil->shouldReceive('resolveChatCapabilities')
            ->once()
            ->with($businessId, $userId)
            ->andReturn($capabilities);

        return $chatUtil;
    }

    protected function makeUser(int $id, array $abilities): User
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
