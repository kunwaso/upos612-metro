<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUserIdToProjectxChatMemoryTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_chat_memory')) {
            return;
        }

        if (! Schema::hasColumn('projectx_chat_memory', 'user_id')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->unsignedInteger('user_id')->nullable()->after('business_id');
            });
        }

        if ($this->indexExists('projectx_chat_memory', 'projectx_chat_memory_business_key_unique')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->dropUnique('projectx_chat_memory_business_key_unique');
            });
        }

        if (! $this->indexExists('projectx_chat_memory', 'projectx_chat_memory_business_user_idx')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->index(['business_id', 'user_id'], 'projectx_chat_memory_business_user_idx');
            });
        }

        if (! $this->indexExists('projectx_chat_memory', 'projectx_chat_memory_business_user_key_unique')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->unique(['business_id', 'user_id', 'memory_key'], 'projectx_chat_memory_business_user_key_unique');
            });
        }

        if (
            Schema::hasColumn('projectx_chat_memory', 'user_id')
            && ! $this->foreignKeyExists('projectx_chat_memory', 'projectx_chat_memory_user_id_foreign')
        ) {
            try {
                Schema::table('projectx_chat_memory', function (Blueprint $table) {
                    $table->foreign('user_id', 'projectx_chat_memory_user_id_foreign')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $exception) {
                // Some deployments use MyISAM; keep migration resilient if FK cannot be materialized.
            }
        }
    }

    public function down()
    {
        if (! Schema::hasTable('projectx_chat_memory')) {
            return;
        }

        if ($this->foreignKeyExists('projectx_chat_memory', 'projectx_chat_memory_user_id_foreign')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->dropForeign('projectx_chat_memory_user_id_foreign');
            });
        }

        if ($this->indexExists('projectx_chat_memory', 'projectx_chat_memory_business_user_key_unique')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->dropUnique('projectx_chat_memory_business_user_key_unique');
            });
        }

        if ($this->indexExists('projectx_chat_memory', 'projectx_chat_memory_business_user_idx')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->dropIndex('projectx_chat_memory_business_user_idx');
            });
        }

        if (Schema::hasColumn('projectx_chat_memory', 'user_id')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        if (! $this->indexExists('projectx_chat_memory', 'projectx_chat_memory_business_key_unique')) {
            Schema::table('projectx_chat_memory', function (Blueprint $table) {
                $table->unique(['business_id', 'memory_key'], 'projectx_chat_memory_business_key_unique');
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
