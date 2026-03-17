<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUserIdToAichatChatMemoryTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('aichat_chat_memory')) {
            return;
        }

        if (! Schema::hasColumn('aichat_chat_memory', 'user_id')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->unsignedInteger('user_id')->nullable()->after('business_id');
            });
        }

        if ($this->indexExists('aichat_chat_memory', 'aichat_chat_memory_business_key_unique')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->dropUnique('aichat_chat_memory_business_key_unique');
            });
        }

        if (! $this->indexExists('aichat_chat_memory', 'aichat_chat_memory_business_user_idx')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->index(['business_id', 'user_id'], 'aichat_chat_memory_business_user_idx');
            });
        }

        if (! $this->indexExists('aichat_chat_memory', 'aichat_chat_memory_business_user_key_unique')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->unique(['business_id', 'user_id', 'memory_key'], 'aichat_chat_memory_business_user_key_unique');
            });
        }

        if (Schema::hasColumn('aichat_chat_memory', 'user_id') && ! $this->foreignKeyExists('aichat_chat_memory', 'aichat_chat_memory_user_id_foreign')) {
            try {
                Schema::table('aichat_chat_memory', function (Blueprint $table) {
                    $table->foreign('user_id', 'aichat_chat_memory_user_id_foreign')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $exception) {
                // Keep migration resilient for deployments that cannot materialize foreign keys.
            }
        }
    }

    public function down()
    {
        if (! Schema::hasTable('aichat_chat_memory')) {
            return;
        }

        if ($this->foreignKeyExists('aichat_chat_memory', 'aichat_chat_memory_user_id_foreign')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->dropForeign('aichat_chat_memory_user_id_foreign');
            });
        }

        if ($this->indexExists('aichat_chat_memory', 'aichat_chat_memory_business_user_key_unique')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->dropUnique('aichat_chat_memory_business_user_key_unique');
            });
        }

        if ($this->indexExists('aichat_chat_memory', 'aichat_chat_memory_business_user_idx')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->dropIndex('aichat_chat_memory_business_user_idx');
            });
        }

        if (Schema::hasColumn('aichat_chat_memory', 'user_id')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        if (! $this->indexExists('aichat_chat_memory', 'aichat_chat_memory_business_key_unique')) {
            Schema::table('aichat_chat_memory', function (Blueprint $table) {
                $table->unique(['business_id', 'memory_key'], 'aichat_chat_memory_business_key_unique');
            });
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    protected function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
}
