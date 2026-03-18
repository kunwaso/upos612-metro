<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatProductQuoteDraftsTable extends Migration
{
    public function up()
    {
        Schema::create('aichat_product_quote_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->uuid('conversation_id')->nullable();
            $table->bigInteger('telegram_chat_id')->nullable();
            $table->enum('flow', ['multi', 'single'])->default('multi');
            $table->enum('status', ['collecting', 'ready', 'consumed', 'expired'])->default('collecting');
            $table->json('payload')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->dateTime('last_interaction_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('aichat_chat_conversations')->onDelete('cascade');

            $table->index(['business_id', 'user_id', 'conversation_id', 'updated_at'], 'aichat_quote_draft_business_user_conv_updated_idx');
            $table->index(['business_id', 'user_id', 'telegram_chat_id', 'updated_at'], 'aichat_quote_draft_business_user_tgchat_updated_idx');
            $table->index(['business_id', 'status', 'expires_at'], 'aichat_quote_draft_business_status_exp_idx');
            $table->index(['conversation_id', 'updated_at'], 'aichat_quote_draft_conv_updated_idx');
            $table->index(['telegram_chat_id', 'updated_at'], 'aichat_quote_draft_tgchat_updated_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aichat_product_quote_drafts');
    }
}
