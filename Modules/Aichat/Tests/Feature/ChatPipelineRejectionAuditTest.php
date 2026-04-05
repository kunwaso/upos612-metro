<?php

namespace Modules\Aichat\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Aichat\Entities\ChatAuditLog;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Http\Controllers\ChatController;
use Modules\Aichat\Http\Requests\Chat\SendChatMessageRequest;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatUtil;
use Modules\Aichat\Utils\ChatWorkflowUtil;
use Tests\TestCase;

class ChatPipelineRejectionAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('aichat_chat_conversations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('aichat_chat_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('conversation_id')->nullable();
            $table->string('action', 100);
            $table->string('provider', 32)->nullable();
            $table->string('model', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('aichat_chat_audit_logs');
        Schema::dropIfExists('aichat_chat_conversations');
        DB::disconnect('sqlite');

        \Mockery::close();

        parent::tearDown();
    }

    public function test_send_model_invalid_writes_pipeline_rejected_audit_row(): void
    {
        $this->be($this->makeUser(42));

        $conversation = $this->createConversation('conv-001', 10, 42);

        $session = \Mockery::mock();
        $session->shouldReceive('get')->with('user.business_id')->andReturn(10);

        $request = \Mockery::mock(SendChatMessageRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->andReturn(['prompt' => 'hello', 'provider' => 'openai', 'model' => 'bad-model', 'channel' => 'web']);

        $chatUtil = app(ChatUtil::class);

        $workflowUtil = \Mockery::mock(ChatWorkflowUtil::class);
        $workflowUtil->shouldReceive('prepareSendOrStreamContext')
            ->once()
            ->andReturn([
                'success' => false,
                'error_type' => 'model_invalid',
                'error_message' => 'Model not allowed.',
            ]);

        $aiChatUtil = \Mockery::mock(AIChatUtil::class);

        $controller = new ChatController($chatUtil, $aiChatUtil, $workflowUtil);
        $response = $controller->send($request, 'conv-001');

        $this->assertEquals(422, $response->getStatusCode());

        $row = ChatAuditLog::where('action', 'chat_message_pipeline_rejected')->first();
        $this->assertNotNull($row, 'Expected a chat_message_pipeline_rejected audit row to be written.');
        $this->assertEquals(10, $row->business_id);
        $this->assertEquals('model_invalid', data_get($row->metadata, 'error_type'));
        $this->assertEquals('web', data_get($row->metadata, 'channel'));
    }

    public function test_send_pii_blocked_writes_pipeline_rejected_audit_row(): void
    {
        $this->be($this->makeUser(42));

        $this->createConversation('conv-002', 10, 42);

        $session = \Mockery::mock();
        $session->shouldReceive('get')->with('user.business_id')->andReturn(10);

        $request = \Mockery::mock(SendChatMessageRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->andReturn(['prompt' => 'my password is secret', 'provider' => 'openai', 'model' => 'gpt-4o-mini', 'channel' => 'web']);

        $chatUtil = app(ChatUtil::class);

        $workflowUtil = \Mockery::mock(ChatWorkflowUtil::class);
        $workflowUtil->shouldReceive('prepareSendOrStreamContext')
            ->once()
            ->andReturn([
                'success' => false,
                'error_type' => 'pii_blocked',
                'error_message' => 'Sensitive data blocked.',
            ]);

        $aiChatUtil = \Mockery::mock(AIChatUtil::class);

        $controller = new ChatController($chatUtil, $aiChatUtil, $workflowUtil);
        $response = $controller->send($request, 'conv-002');

        $this->assertEquals(422, $response->getStatusCode());

        $row = ChatAuditLog::where('action', 'chat_message_pipeline_rejected')->first();
        $this->assertNotNull($row, 'Expected a chat_message_pipeline_rejected audit row to be written.');
        $this->assertEquals('pii_blocked', data_get($row->metadata, 'error_type'));
        $this->assertEquals('web', data_get($row->metadata, 'channel'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function createConversation(string $id, int $businessId, int $userId): ChatConversation
    {
        return ChatConversation::forceCreate([
            'id' => $id,
            'business_id' => $businessId,
            'user_id' => $userId,
        ]);
    }

    protected function makeUser(int $id = 1): \App\User
    {
        $user = \Mockery::mock(\App\User::class)->makePartial();
        $user->id = $id;
        $user->shouldReceive('can')->andReturn(true);
        $user->shouldReceive('getAuthIdentifier')->andReturn($id);
        $user->shouldReceive('getAuthIdentifierName')->andReturn('id');

        return $user;
    }
}
