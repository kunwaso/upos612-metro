<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReasoningRulesToAichatChatSettingsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('aichat_chat_settings')) {
            return;
        }

        if (! Schema::hasColumn('aichat_chat_settings', 'reasoning_rules')) {
            Schema::table('aichat_chat_settings', function (Blueprint $table) {
                $table->text('reasoning_rules')->nullable()->after('system_prompt');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('aichat_chat_settings')) {
            return;
        }

        if (Schema::hasColumn('aichat_chat_settings', 'reasoning_rules')) {
            Schema::table('aichat_chat_settings', function (Blueprint $table) {
                $table->dropColumn('reasoning_rules');
            });
        }
    }
}

