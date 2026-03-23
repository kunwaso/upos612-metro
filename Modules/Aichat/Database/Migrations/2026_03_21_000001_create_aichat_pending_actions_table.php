<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatPendingActionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('aichat_pending_actions')) {
            return;
        }

        Schema::create('aichat_pending_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->uuid('conversation_id');
            $table->unsignedInteger('user_id');
            $table->string('channel', 20)->default('web');
            $table->string('module', 50);
            $table->string('action', 50);
            $table->string('status', 20)->default('pending');
            $table->string('target_type', 50)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->json('payload')->nullable();
            $table->json('result_payload')->nullable();
            $table->text('preview_text')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('aichat_chat_conversations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['business_id', 'user_id', 'conversation_id'], 'aichat_pending_actions_owner_idx');
            $table->index(['status', 'expires_at'], 'aichat_pending_actions_status_expiry_idx');
            $table->index(['module', 'action'], 'aichat_pending_actions_module_action_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aichat_pending_actions');
    }
}

