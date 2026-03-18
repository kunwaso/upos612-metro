<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterAichatProductQuoteDraftsForTelegramChannel extends Migration
{
    protected string $table = 'aichat_product_quote_drafts';

    public function up()
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (! Schema::hasColumn($this->table, 'telegram_chat_id')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->bigInteger('telegram_chat_id')->nullable()->after('conversation_id');
            });
        }

        $this->makeConversationIdNullable();
        $this->createIndexIfMissing('aichat_quote_draft_business_user_conv_updated_idx', 'business_id, user_id, conversation_id, updated_at');
        $this->createIndexIfMissing('aichat_quote_draft_business_user_tgchat_updated_idx', 'business_id, user_id, telegram_chat_id, updated_at');
        $this->createIndexIfMissing('aichat_quote_draft_tgchat_updated_idx', 'telegram_chat_id, updated_at');
    }

    public function down()
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $this->dropIndexIfExists('aichat_quote_draft_business_user_conv_updated_idx');
        $this->dropIndexIfExists('aichat_quote_draft_business_user_tgchat_updated_idx');
        $this->dropIndexIfExists('aichat_quote_draft_tgchat_updated_idx');

        if (Schema::hasColumn($this->table, 'telegram_chat_id')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('telegram_chat_id');
            });
        }
    }

    protected function makeConversationIdNullable(): void
    {
        if (! Schema::hasColumn($this->table, 'conversation_id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `' . $this->table . '` MODIFY `conversation_id` CHAR(36) NULL');

                return;
            }

            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE "' . $this->table . '" ALTER COLUMN "conversation_id" DROP NOT NULL');
            }
        } catch (\Throwable $exception) {
            // Keep migration resilient for engines that cannot modify in-place (ex: sqlite).
        }
    }

    protected function createIndexIfMissing(string $indexName, string $columns): void
    {
        try {
            DB::statement('CREATE INDEX `' . $indexName . '` ON `' . $this->table . '` (' . $columns . ')');
        } catch (\Throwable $exception) {
            // Ignore when index already exists.
        }
    }

    protected function dropIndexIfExists(string $indexName): void
    {
        try {
            DB::statement('DROP INDEX `' . $indexName . '` ON `' . $this->table . '`');
        } catch (\Throwable $exception) {
            // Ignore when index is missing.
        }
    }
}

