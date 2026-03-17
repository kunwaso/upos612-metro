<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatChatSettingsAndAuditTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('aichat_chat_settings')) {
            Schema::create('aichat_chat_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->unique();
                $table->boolean('enabled')->default(true);
                $table->string('default_provider', 32)->nullable();
                $table->string('default_model', 120)->nullable();
                $table->text('system_prompt')->nullable();
                $table->json('model_allowlist')->nullable();
                $table->unsignedInteger('retention_days')->nullable();
                $table->enum('pii_policy', ['off', 'warn', 'block'])->default('warn');
                $table->boolean('moderation_enabled')->default(false);
                $table->text('moderation_terms')->nullable();
                $table->unsignedInteger('idle_timeout_minutes')->default(30);
                $table->json('suggested_replies')->nullable();
                $table->unsignedInteger('share_ttl_hours')->default(168);
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('aichat_chat_audit_logs')) {
            Schema::create('aichat_chat_audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->uuid('conversation_id')->nullable();
                $table->string('action', 100);
                $table->string('provider', 32)->nullable();
                $table->string('model', 120)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('conversation_id')->references('id')->on('aichat_chat_conversations')->onDelete('set null');

                $table->index(['business_id', 'created_at'], 'aichat_chat_audit_business_created_idx');
                $table->index(['conversation_id', 'created_at'], 'aichat_chat_audit_conversation_created_idx');
                $table->index(['action', 'created_at'], 'aichat_chat_audit_action_created_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('aichat_chat_audit_logs');
        Schema::dropIfExists('aichat_chat_settings');
    }
}
