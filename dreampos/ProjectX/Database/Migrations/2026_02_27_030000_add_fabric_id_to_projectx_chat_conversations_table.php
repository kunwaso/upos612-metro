<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFabricIdToProjectxChatConversationsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_chat_conversations')) {
            return;
        }

        if (! Schema::hasColumn('projectx_chat_conversations', 'fabric_id')) {
            Schema::table('projectx_chat_conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('fabric_id')->nullable()->after('user_id');
                $table->foreign('fabric_id', 'projectx_chat_conv_fabric_fk')
                    ->references('id')
                    ->on('projectx_fabrics')
                    ->onDelete('cascade');
                $table->index(
                    ['business_id', 'user_id', 'fabric_id', 'updated_at'],
                    'projectx_chat_conv_business_user_fabric_updated_idx'
                );
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('projectx_chat_conversations')) {
            return;
        }

        if (Schema::hasColumn('projectx_chat_conversations', 'fabric_id')) {
            Schema::table('projectx_chat_conversations', function (Blueprint $table) {
                $table->dropIndex('projectx_chat_conv_business_user_fabric_updated_idx');
                $table->dropForeign('projectx_chat_conv_fabric_fk');
                $table->dropColumn('fabric_id');
            });
        }
    }
}
