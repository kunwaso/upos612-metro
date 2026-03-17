<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFabricContextColumnsToProjectxChatMessagesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_chat_messages')) {
            return;
        }

        if (! Schema::hasColumn('projectx_chat_messages', 'fabric_id')) {
            Schema::table('projectx_chat_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('fabric_id')->nullable()->after('conversation_id');
                $table->boolean('fabric_insight')->default(false)->after('fabric_id');
                $table->foreign('fabric_id', 'projectx_chat_msg_fabric_fk')
                    ->references('id')
                    ->on('projectx_fabrics')
                    ->onDelete('set null');
                $table->index(
                    ['business_id', 'fabric_id', 'created_at'],
                    'projectx_chat_msg_business_fabric_created_idx'
                );
                $table->index(
                    ['conversation_id', 'fabric_insight', 'id'],
                    'projectx_chat_msg_conv_insight_id_idx'
                );
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('projectx_chat_messages')) {
            return;
        }

        if (Schema::hasColumn('projectx_chat_messages', 'fabric_id')) {
            Schema::table('projectx_chat_messages', function (Blueprint $table) {
                $table->dropIndex('projectx_chat_msg_business_fabric_created_idx');
                $table->dropIndex('projectx_chat_msg_conv_insight_id_idx');
                $table->dropForeign('projectx_chat_msg_fabric_fk');
                $table->dropColumn(['fabric_id', 'fabric_insight']);
            });
        }
    }
}
