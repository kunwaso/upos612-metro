<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxChatMessagesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_chat_messages')) {
            return;
        }

        Schema::create('projectx_chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('conversation_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->enum('role', ['user', 'assistant', 'error', 'system'])->default('user');
            $table->longText('content');
            $table->string('provider', 32)->nullable();
            $table->string('model', 120)->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('projectx_chat_conversations')
                ->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['conversation_id', 'created_at'], 'projectx_chat_msg_conversation_created_idx');
            $table->index(['business_id', 'created_at'], 'projectx_chat_msg_business_created_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_chat_messages');
    }
}

