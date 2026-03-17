<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatTelegramTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('aichat_telegram_bots')) {
            Schema::create('aichat_telegram_bots', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->unique();
                $table->unsignedInteger('linked_user_id');
                $table->string('webhook_key', 128)->unique();
                $table->string('webhook_secret_token', 128)->nullable();
                $table->longText('encrypted_bot_token');
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('linked_user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('aichat_telegram_allowed_users')) {
            Schema::create('aichat_telegram_allowed_users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('user_id');
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['business_id', 'user_id'], 'aichat_tg_allowed_users_business_user_unique');
            });
        }

        if (! Schema::hasTable('aichat_telegram_chats')) {
            Schema::create('aichat_telegram_chats', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->bigInteger('telegram_chat_id');
                $table->uuid('conversation_id');
                $table->unsignedInteger('user_id');
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('conversation_id')->references('id')->on('aichat_chat_conversations')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['business_id', 'telegram_chat_id'], 'aichat_tg_chats_business_chat_unique');
                $table->index(['business_id', 'conversation_id'], 'aichat_tg_chats_business_conversation_idx');
            });
        }

        if (! Schema::hasTable('aichat_telegram_link_codes')) {
            Schema::create('aichat_telegram_link_codes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('user_id');
                $table->string('code', 32)->unique();
                $table->dateTime('expires_at');
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['business_id', 'user_id'], 'aichat_tg_link_codes_business_user_idx');
                $table->index(['expires_at'], 'aichat_tg_link_codes_expires_idx');
            });
        }

        if (! Schema::hasTable('aichat_telegram_allowed_groups')) {
            Schema::create('aichat_telegram_allowed_groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->bigInteger('telegram_chat_id');
                $table->string('title', 255)->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->unique(['business_id', 'telegram_chat_id'], 'aichat_tg_allowed_groups_business_chat_unique');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('aichat_telegram_allowed_groups');
        Schema::dropIfExists('aichat_telegram_link_codes');
        Schema::dropIfExists('aichat_telegram_chats');
        Schema::dropIfExists('aichat_telegram_allowed_users');
        Schema::dropIfExists('aichat_telegram_bots');
    }
}
