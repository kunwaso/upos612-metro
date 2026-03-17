<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAichatChatMemoryTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('aichat_chat_memory')) {
            return;
        }

        Schema::create('aichat_chat_memory', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('memory_key', 150);
            $table->text('memory_value');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['business_id', 'memory_key'], 'aichat_chat_memory_business_key_unique');
            $table->index(['business_id', 'updated_at'], 'aichat_chat_memory_business_updated_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aichat_chat_memory');
    }
}
