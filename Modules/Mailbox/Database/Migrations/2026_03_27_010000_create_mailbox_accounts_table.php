<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailboxAccountsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('mailbox_accounts')) {
            return;
        }

        Schema::create('mailbox_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->string('provider', 20);
            $table->string('display_name', 120)->nullable();
            $table->string('sender_name', 120)->nullable();
            $table->string('email_address', 191);
            $table->string('provider_account_id', 191)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->longText('encrypted_access_token')->nullable();
            $table->longText('encrypted_refresh_token')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->string('imap_host', 191)->nullable();
            $table->unsignedSmallInteger('imap_port')->nullable();
            $table->string('imap_encryption', 20)->nullable();
            $table->string('imap_username', 191)->nullable();
            $table->longText('encrypted_imap_password')->nullable();
            $table->string('imap_inbox_folder', 120)->nullable();
            $table->string('imap_sent_folder', 120)->nullable();
            $table->string('imap_trash_folder', 120)->nullable();
            $table->string('smtp_host', 191)->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 20)->nullable();
            $table->string('smtp_username', 191)->nullable();
            $table->longText('encrypted_smtp_password')->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('sync_cursor_json')->nullable();
            $table->json('provider_meta_json')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->dateTime('last_tested_at')->nullable();
            $table->dateTime('last_sync_error_at')->nullable();
            $table->string('last_sync_error_message', 500)->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['business_id', 'user_id', 'provider', 'email_address'], 'mailbox_accounts_scope_unique');
            $table->index(['business_id', 'user_id', 'is_active'], 'mailbox_accounts_owner_idx');
            $table->index(['provider', 'sync_enabled', 'is_active'], 'mailbox_accounts_sync_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mailbox_accounts');
    }
}
