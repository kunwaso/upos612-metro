<?php

namespace Modules\Aichat\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Aichat\Utils\ChatAuditUtil;
use Modules\Aichat\Utils\ChatMessageRendererUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatUtilTelegramConversationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->nullable();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('aichat_chat_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->string('title')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->text('last_message_preview')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->string('last_model')->nullable();
            $table->timestamps();
        });

        Schema::create('aichat_telegram_chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->bigInteger('telegram_chat_id');
            $table->uuid('conversation_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        Schema::create('aichat_telegram_allowed_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        Schema::create('aichat_telegram_link_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->string('code');
            $table->dateTime('expires_at');
            $table->timestamps();
        });

        Schema::create('aichat_telegram_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('linked_user_id');
            $table->string('webhook_key');
            $table->string('webhook_secret_token')->nullable();
            $table->longText('encrypted_bot_token');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('aichat_telegram_bots');
        Schema::dropIfExists('aichat_telegram_link_codes');
        Schema::dropIfExists('aichat_telegram_allowed_users');
        Schema::dropIfExists('aichat_telegram_chats');
        Schema::dropIfExists('aichat_chat_conversations');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_get_or_create_telegram_conversation_reuses_existing_mapping()
    {
        $chatUtil = $this->makeChatUtil();

        $first = $chatUtil->getOrCreateTelegramConversation(1, 123456, 9);
        $second = $chatUtil->getOrCreateTelegramConversation(1, 123456, 9);

        $this->assertSame((string) $first->id, (string) $second->id);
        $this->assertSame(1, DB::table('aichat_telegram_chats')->count());
    }

    public function test_link_code_flow_creates_chat_and_consumes_code()
    {
        DB::table('users')->insert([
            'id' => 5,
            'business_id' => 1,
            'first_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chatUtil = $this->makeChatUtil();
        $chatUtil->syncTelegramAllowedUsers(1, [5]);

        $code = $chatUtil->createTelegramLinkCode(1, 5);
        $telegramChat = $chatUtil->consumeTelegramLinkCode(1, $code, 777001);

        $this->assertNotNull($telegramChat);
        $this->assertSame(5, (int) $telegramChat->user_id);
        $this->assertSame(0, DB::table('aichat_telegram_link_codes')->count());
    }

    public function test_get_or_create_telegram_link_code_reuses_active_code_until_regenerated()
    {
        DB::table('users')->insert([
            'id' => 6,
            'business_id' => 1,
            'first_name' => 'Reuse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chatUtil = $this->makeChatUtil();
        $chatUtil->syncTelegramAllowedUsers(1, [6]);

        $firstCode = $chatUtil->getOrCreateTelegramLinkCode(1, 6);
        $secondCode = $chatUtil->getOrCreateTelegramLinkCode(1, 6);
        $regeneratedCode = $chatUtil->createTelegramLinkCode(1, 6);

        $this->assertSame($firstCode, $secondCode);
        $this->assertNotSame($firstCode, $regeneratedCode);
        $this->assertSame(1, DB::table('aichat_telegram_link_codes')->count());
        $this->assertSame($regeneratedCode, $chatUtil->getActiveTelegramLinkCode(1, 6));
    }

    public function test_split_telegram_message_chunks_to_limit()
    {
        $chatUtil = $this->makeChatUtil();
        $chunks = $chatUtil->splitTelegramMessage(str_repeat('a', 9000), 4096);

        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(4096, mb_strlen($chunk));
        }
    }

    public function test_normalize_telegram_outbound_text_strips_markdown_markers()
    {
        $chatUtil = $this->makeChatUtil();

        $normalized = $chatUtil->normalizeTelegramOutboundText(
            "1. **Product A**\n* **SKU:** PE-Roll-38664\n* **Unit Price:** 190,000.00"
        );

        $this->assertSame(
            "1. Product A\n- SKU: PE-Roll-38664\n- Unit Price: 190,000.00",
            $normalized
        );
    }

    protected function makeChatUtil(): ChatUtil
    {
        $chatAuditUtil = \Mockery::mock(ChatAuditUtil::class);
        $rendererUtil = \Mockery::mock(ChatMessageRendererUtil::class);

        return new ChatUtil($chatAuditUtil, $rendererUtil);
    }
}
