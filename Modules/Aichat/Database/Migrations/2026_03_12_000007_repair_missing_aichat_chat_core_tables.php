<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RepairMissingAichatChatCoreTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('aichat_chat_conversations')) {
            Schema::create('aichat_chat_conversations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('user_id');
                $table->string('title', 255)->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->boolean('is_archived')->default(false);
                $table->text('last_message_preview')->nullable();
                $table->dateTime('last_message_at')->nullable();
                $table->string('last_model', 120)->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                $table->index(['user_id', 'business_id', 'updated_at'], 'aichat_chat_conv_user_business_updated_idx');
                $table->index(['user_id', 'business_id', 'is_archived', 'updated_at'], 'aichat_chat_conv_user_business_archived_idx');
                $table->index(['business_id', 'updated_at'], 'aichat_chat_conv_business_updated_idx');
            });
        }

        if (! Schema::hasTable('aichat_chat_messages')) {
            Schema::create('aichat_chat_messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('conversation_id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->enum('role', ['user', 'assistant', 'error', 'system'])->default('user');
                $table->longText('content');
                $table->string('provider', 32)->nullable();
                $table->string('model', 120)->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('aichat_chat_conversations')->onDelete('cascade');
                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

                $table->index(['conversation_id', 'created_at'], 'aichat_chat_msg_conversation_created_idx');
                $table->index(['business_id', 'created_at'], 'aichat_chat_msg_business_created_idx');
            });
        }
    }

    public function down()
    {
        // Intentionally left blank. This migration repairs drift between the
        // migrations log and the live schema and should not drop recovered data.
    }
}
