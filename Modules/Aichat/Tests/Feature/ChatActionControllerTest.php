<?php

namespace Modules\Aichat\Tests\Feature;

use App\User;
use Modules\Aichat\Entities\ChatPendingAction;
use Modules\Aichat\Http\Requests\Chat\CancelChatActionRequest;
use Modules\Aichat\Http\Controllers\ChatActionController;
use Modules\Aichat\Http\Requests\Chat\ConfirmChatActionRequest;
use Modules\Aichat\Http\Requests\Chat\PrepareChatActionRequest;
use Modules\Aichat\Utils\ChatActionUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatActionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aichat.actions.enabled', true);
    }

    protected function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function test_prepare_returns_success_payload()
    {
        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(PrepareChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn([
            'module' => 'products',
            'action' => 'update',
            'payload' => ['id' => 15, 'name' => 'Updated Name'],
            'channel' => 'web',
        ]);

        $pendingAction = new ChatPendingAction([
            'id' => 99,
            'module' => 'products',
            'action' => 'update',
            'status' => ChatPendingAction::STATUS_PENDING,
            'channel' => 'web',
            'payload' => ['id' => 15, 'name' => 'Updated Name'],
            'preview_text' => 'Update product #15',
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('prepareAction')
            ->once()
            ->with(44, 1, '00000000-0000-0000-0000-000000000111', \Mockery::type('array'), 'web')
            ->andReturn($pendingAction);
        $chatActionUtil->shouldReceive('serializePendingAction')->once()->with($pendingAction)->andReturn([
            'id' => 99,
            'module' => 'products',
            'action' => 'update',
            'status' => 'pending',
        ]);

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->prepare($request, '00000000-0000-0000-0000-000000000111');

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame('products', data_get($payload, 'data.module'));
    }

    public function test_confirm_returns_forbidden_shape_when_permission_is_denied()
    {
        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(ConfirmChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn([]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('audit')->once();

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('confirmAction')
            ->once()
            ->andThrow(new \RuntimeException(__('aichat::lang.chat_action_forbidden')));

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->confirm($request, '00000000-0000-0000-0000-000000000111', 56);

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('forbidden', (string) ($payload['code'] ?? ''));
    }

    public function test_pending_returns_items_and_catalog()
    {
        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('session')->andReturn($session);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('resolveChatCapabilities')->once()->with(44, 1)->andReturn([
            'products' => ['create' => true],
        ]);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('listPendingActions')
            ->once()
            ->with(44, 1, '00000000-0000-0000-0000-000000000111')
            ->andReturn([]);
        $chatActionUtil->shouldReceive('getActionCatalog')->once()->andReturn([]);

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->pending($request, '00000000-0000-0000-0000-000000000111');

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame([], data_get($payload, 'data.items'));
    }

    public function test_prepare_returns_forbidden_when_chat_is_disabled()
    {
        config()->set('aichat.actions.enabled', true);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(PrepareChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldNotReceive('validated');

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(false);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('prepareAction');

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->prepare($request, '00000000-0000-0000-0000-000000000111');

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
    }

    public function test_prepare_returns_invalid_action_shape_when_payload_is_invalid()
    {
        config()->set('aichat.actions.enabled', true);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(PrepareChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn([
            'module' => 'products',
            'action' => 'update',
            'payload' => [],
            'channel' => 'web',
        ]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('prepareAction')
            ->once()
            ->andThrow(new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload')));

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->prepare($request, '00000000-0000-0000-0000-000000000111');

        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('invalid_action', (string) ($payload['code'] ?? ''));
    }

    public function test_confirm_returns_not_found_shape_when_action_is_missing()
    {
        config()->set('aichat.actions.enabled', true);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(ConfirmChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn([]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('confirmAction')
            ->once()
            ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->confirm($request, '00000000-0000-0000-0000-000000000111', 56);

        $this->assertSame(404, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('not_found', (string) ($payload['code'] ?? ''));
    }

    public function test_cancel_returns_forbidden_shape_and_audits_denial()
    {
        config()->set('aichat.actions.enabled', true);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(CancelChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn([]);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('audit')->once();

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('cancelAction')
            ->once()
            ->andThrow(new \RuntimeException(__('aichat::lang.chat_action_forbidden')));

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->cancel($request, '00000000-0000-0000-0000-000000000111', 87);

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('forbidden', (string) ($payload['code'] ?? ''));
    }

    public function test_pending_returns_not_found_shape_when_conversation_is_missing()
    {
        config()->set('aichat.actions.enabled', true);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('session')->andReturn($session);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldReceive('resolveChatCapabilities')->once()->with(44, 1)->andReturn([]);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldReceive('listPendingActions')
            ->once()
            ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());
        $chatActionUtil->shouldNotReceive('getActionCatalog');

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->pending($request, '00000000-0000-0000-0000-000000000111');

        $this->assertSame(404, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('not_found', (string) ($payload['code'] ?? ''));
    }

    public function test_prepare_returns_feature_disabled_when_actions_are_disabled()
    {
        config()->set('aichat.actions.enabled', false);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(PrepareChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldNotReceive('validated');

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('prepareAction');

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->prepare($request, '00000000-0000-0000-0000-000000000111');

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('feature_disabled', (string) ($payload['code'] ?? ''));
    }

    public function test_pending_returns_feature_disabled_when_actions_are_disabled()
    {
        config()->set('aichat.actions.enabled', false);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('session')->andReturn($session);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $chatUtil->shouldNotReceive('resolveChatCapabilities');

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('listPendingActions');

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->pending($request, '00000000-0000-0000-0000-000000000111');

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('feature_disabled', (string) ($payload['code'] ?? ''));
    }

    public function test_confirm_returns_feature_disabled_when_actions_are_disabled()
    {
        config()->set('aichat.actions.enabled', false);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(ConfirmChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldNotReceive('validated');

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('confirmAction');

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->confirm($request, '00000000-0000-0000-0000-000000000111', 42);

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('feature_disabled', (string) ($payload['code'] ?? ''));
    }

    public function test_cancel_returns_feature_disabled_when_actions_are_disabled()
    {
        config()->set('aichat.actions.enabled', false);

        $this->be($this->makeUser(1, ['aichat.chat.edit' => true]));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(CancelChatActionRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldNotReceive('validated');

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class);
        $chatActionUtil->shouldNotReceive('cancelAction');

        $controller = new ChatActionController($chatUtil, $chatActionUtil);
        $response = $controller->cancel($request, '00000000-0000-0000-0000-000000000111', 42);

        $this->assertSame(403, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('feature_disabled', (string) ($payload['code'] ?? ''));
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
