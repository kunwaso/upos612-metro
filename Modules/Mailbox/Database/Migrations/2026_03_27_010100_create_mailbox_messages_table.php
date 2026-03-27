<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailboxMessagesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('mailbox_messages')) {
            return;
        }

        Schema::create('mailbox_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('mailbox_account_id');
            $table->string('provider', 20);
            $table->string('provider_message_id', 191);
            $table->string('provider_thread_id', 191)->nullable();
            $table->string('thread_key', 191);
            $table->string('folder', 40)->default('inbox');
            $table->string('internet_message_id', 255)->nullable();
            $table->string('subject', 500)->nullable();
            $table->text('snippet')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('from_json')->nullable();
            $table->json('to_json')->nullable();
            $table->json('cc_json')->nullable();
            $table->json('bcc_json')->nullable();
            $table->json('reply_to_json')->nullable();
            $table->json('labels_json')->nullable();
            $table->json('references_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_important')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('provider_updated_at')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('mailbox_account_id')->references('id')->on('mailbox_accounts')->onDelete('cascade');

            $table->unique(['mailbox_account_id', 'provider_message_id'], 'mailbox_messages_provider_unique');
            $table->index(['business_id', 'user_id', 'folder', 'received_at'], 'mailbox_messages_folder_idx');
            $table->index(['mailbox_account_id', 'thread_key'], 'mailbox_messages_thread_idx');
            $table->index(['mailbox_account_id', 'provider_thread_id'], 'mailbox_messages_provider_thread_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mailbox_messages');
    }
}
