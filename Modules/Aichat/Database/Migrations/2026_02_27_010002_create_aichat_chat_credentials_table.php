<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatChatCredentialsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('aichat_chat_credentials')) {
            return;
        }

        Schema::create('aichat_chat_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('provider', 32);
            $table->longText('encrypted_api_key');
            $table->boolean('is_active')->default(true);
            $table->dateTime('rotated_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['business_id', 'user_id', 'provider', 'is_active'], 'aichat_chat_cred_scope_active_idx');
            $table->index(['business_id', 'provider', 'is_active'], 'aichat_chat_cred_business_provider_idx');
            $table->index(['business_id', 'updated_at'], 'aichat_chat_cred_business_updated_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aichat_chat_credentials');
    }
}
