<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatChatMessageFeedbackTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('aichat_chat_message_feedback')) {
            return;
        }

        Schema::create('aichat_chat_message_feedback', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('message_id');
            $table->unsignedInteger('user_id');
            $table->enum('feedback', ['up', 'down']);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('aichat_chat_conversations')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('aichat_chat_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['business_id', 'message_id'], 'aichat_chat_feedback_business_message_idx');
            $table->index(['business_id', 'user_id', 'created_at'], 'aichat_chat_feedback_business_user_created_idx');
            $table->unique(['message_id', 'user_id'], 'aichat_chat_feedback_message_user_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aichat_chat_message_feedback');
    }
}
