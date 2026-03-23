<?php

namespace Modules\Aichat\Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatPendingAction;
use Modules\Aichat\Utils\ChatActionUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatActionLifecycleFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('aichat.actions.enabled', true);
        config()->set('aichat.actions.modules.products', true);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('aichat_chat_conversations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('aichat_pending_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('conversation_id');
            $table->unsignedInteger('user_id');
            $table->string('channel', 20)->default('web');
            $table->string('module', 50);
            $table->string('action', 50);
            $table->string('status', 30);
            $table->string('target_type', 50)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->text('payload')->nullable();
            $table->string('preview_text')->nullable();
            $table->text('result_payload')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('aichat_pending_actions');
        Schema::dropIfExists('aichat_chat_conversations');
        DB::disconnect('sqlite');

        \Mockery::close();

        parent::tearDown();
    }

    public function test_confirm_fails_with_expired_for_stale_pending_action(): void
    {
        $this->be($this->makeUser(11));

        $conversation = $this->createConversation(44, 11);
        $pendingAction = $this->createPendingAction($conversation->id, 44, 11, ChatPendingAction::STATUS_PENDING, now()->subMinute());

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldNotReceive('resolveChatCapabilities');
        $chatUtil->shouldReceive('audit')->once();

        $chatActionUtil = new ChatActionUtil($chatUtil);

        try {
            $chatActionUtil->confirmAction(44, 11, (string) $conversation->id, (int) $pendingAction->id);
            $this->fail('Expected expired action to throw.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(__('aichat::lang.chat_action_expired'), $exception->getMessage());
        }

        $this->assertSame(
            ChatPendingAction::STATUS_PENDING,
            $pendingAction->refresh()->status
        );
    }

    public function test_confirm_is_idempotent_for_already_executed_action_even_after_expiry(): void
    {
        $this->be($this->makeUser(12));

        $conversation = $this->createConversation(44, 12);
        $pendingAction = $this->createPendingAction($conversation->id, 44, 12, ChatPendingAction::STATUS_PENDING, now()->addMinute());

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('resolveChatCapabilities')
            ->once()
            ->with(44, 12)
            ->andReturn([
                'products' => [
                    'create' => true,
                ],
            ]);
        $chatUtil->shouldReceive('audit')->times(2);

        $chatActionUtil = \Mockery::mock(ChatActionUtil::class, [$chatUtil])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $chatActionUtil->shouldReceive('executeAction')
            ->once()
            ->andReturn([
                'entity' => 'product',
                'entity_id' => 123,
                'message' => 'created',
            ]);

        $firstResult = $chatActionUtil->confirmAction(44, 12, (string) $conversation->id, (int) $pendingAction->id);
        $this->assertSame(ChatPendingAction::STATUS_EXECUTED, $firstResult->status);

        $pendingAction->refresh();
        $pendingAction->expires_at = now()->subMinute();
        $pendingAction->save();

        $secondResult = $chatActionUtil->confirmAction(44, 12, (string) $conversation->id, (int) $pendingAction->id);
        $this->assertSame(ChatPendingAction::STATUS_EXECUTED, $secondResult->status);
        $this->assertSame((int) $pendingAction->id, (int) $secondResult->id);
    }

    public function test_confirm_rejects_cancelled_action(): void
    {
        $this->be($this->makeUser(13));

        $conversation = $this->createConversation(44, 13);
        $pendingAction = $this->createPendingAction($conversation->id, 44, 13, ChatPendingAction::STATUS_CANCELLED, now()->addMinute());

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldNotReceive('resolveChatCapabilities');
        $chatUtil->shouldNotReceive('audit');

        $chatActionUtil = new ChatActionUtil($chatUtil);

        try {
            $chatActionUtil->confirmAction(44, 13, (string) $conversation->id, (int) $pendingAction->id);
            $this->fail('Expected cancelled action to throw.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(__('aichat::lang.chat_action_already_cancelled'), $exception->getMessage());
        }

        $this->assertSame(
            ChatPendingAction::STATUS_CANCELLED,
            $pendingAction->refresh()->status
        );
    }

    protected function createConversation(int $businessId, int $userId): ChatConversation
    {
        return ChatConversation::query()->forceCreate([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'business_id' => $businessId,
            'user_id' => $userId,
            'title' => 'Lifecycle Test',
        ]);
    }

    protected function createPendingAction(
        string $conversationId,
        int $businessId,
        int $userId,
        string $status,
        \Illuminate\Support\Carbon $expiresAt
    ): ChatPendingAction {
        return ChatPendingAction::query()->forceCreate([
            'business_id' => $businessId,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'channel' => 'web',
            'module' => 'products',
            'action' => 'create',
            'status' => $status,
            'target_type' => 'product',
            'target_id' => null,
            'payload' => [
                'name' => 'Lifecycle Widget',
                'unit_id' => 1,
            ],
            'preview_text' => 'Create product',
            'result_payload' => null,
            'error_message' => null,
            'confirmed_at' => null,
            'executed_at' => null,
            'expires_at' => $expiresAt,
        ]);
    }

    protected function makeUser(int $id): User
    {
        return new class($id) extends User
        {
            protected int $testUserId;

            public function __construct(int $id)
            {
                parent::__construct();
                $this->testUserId = $id;
                $this->id = $id;
            }

            public function can($ability, $arguments = [])
            {
                return true;
            }
        };
    }
}
