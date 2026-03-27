<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailboxAttachmentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('mailbox_attachments')) {
            return;
        }

        Schema::create('mailbox_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('mailbox_account_id');
            $table->unsignedBigInteger('mailbox_message_id');
            $table->string('provider_attachment_id', 191)->nullable();
            $table->string('filename', 255);
            $table->string('safe_filename', 255);
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('part_id', 120)->nullable();
            $table->string('content_id', 191)->nullable();
            $table->boolean('is_inline')->default(false);
            $table->string('disk', 80)->nullable();
            $table->string('disk_path', 500)->nullable();
            $table->string('hash_sha256', 64)->nullable();
            $table->json('metadata_json')->nullable();
            $table->dateTime('downloaded_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('mailbox_account_id')->references('id')->on('mailbox_accounts')->onDelete('cascade');
            $table->foreign('mailbox_message_id')->references('id')->on('mailbox_messages')->onDelete('cascade');

            $table->index(['mailbox_message_id', 'provider_attachment_id'], 'mailbox_attachments_message_idx');
            $table->index(['business_id', 'user_id'], 'mailbox_attachments_owner_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mailbox_attachments');
    }
}
