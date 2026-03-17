<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatChatConversationsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('aichat_chat_conversations')) {
            return;
        }

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

    public function down()
    {
        Schema::dropIfExists('aichat_chat_conversations');
    }
}
