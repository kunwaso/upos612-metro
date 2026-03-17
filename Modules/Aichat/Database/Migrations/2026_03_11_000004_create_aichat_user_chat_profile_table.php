<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatUserChatProfileTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('aichat_user_chat_profile')) {
            return;
        }

        Schema::create('aichat_user_chat_profile', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->string('display_name', 120)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->text('concerns_topics')->nullable();
            $table->text('preferences')->nullable();
            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['business_id', 'user_id'], 'aichat_user_chat_profile_business_user_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aichat_user_chat_profile');
    }
}

