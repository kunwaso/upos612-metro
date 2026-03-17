<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTrimSupportToProjectxQuoteLinesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_quote_lines')) {
            return;
        }

        if (Schema::hasColumn('projectx_quote_lines', 'fabric_id') && ! $this->isColumnNullable('projectx_quote_lines', 'fabric_id')) {
            DB::statement('ALTER TABLE `projectx_quote_lines` MODIFY `fabric_id` BIGINT UNSIGNED NULL');
        }

        Schema::table('projectx_quote_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_quote_lines', 'trim_id')) {
                $table->unsignedBigInteger('trim_id')->nullable()->after('fabric_id');
            }

            if (! Schema::hasColumn('projectx_quote_lines', 'trim_snapshot')) {
                $table->json('trim_snapshot')->nullable()->after('fabric_snapshot');
            }
        });

        if (! $this->indexExists('projectx_quote_lines', 'projectx_quote_lines_trim_id_index')) {
            Schema::table('projectx_quote_lines', function (Blueprint $table) {
                $table->index('trim_id');
            });
        }

        if (! $this->foreignKeyExists('projectx_quote_lines', 'projectx_quote_lines_trim_id_foreign')) {
            Schema::table('projectx_quote_lines', function (Blueprint $table) {
                $table->foreign('trim_id')
                    ->references('id')
                    ->on('projectx_trims')
                    ->onDelete('restrict');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('projectx_quote_lines')) {
            return;
        }

        if ($this->foreignKeyExists('projectx_quote_lines', 'projectx_quote_lines_trim_id_foreign')) {
            Schema::table('projectx_quote_lines', function (Blueprint $table) {
                $table->dropForeign('projectx_quote_lines_trim_id_foreign');
            });
        }

        if ($this->indexExists('projectx_quote_lines', 'projectx_quote_lines_trim_id_index')) {
            Schema::table('projectx_quote_lines', function (Blueprint $table) {
                $table->dropIndex('projectx_quote_lines_trim_id_index');
            });
        }

        Schema::table('projectx_quote_lines', function (Blueprint $table) {
            if (Schema::hasColumn('projectx_quote_lines', 'trim_id')) {
                $table->dropColumn('trim_id');
            }

            if (Schema::hasColumn('projectx_quote_lines', 'trim_snapshot')) {
                $table->dropColumn('trim_snapshot');
            }
        });

        if (
            Schema::hasColumn('projectx_quote_lines', 'fabric_id')
            && $this->isColumnNullable('projectx_quote_lines', 'fabric_id')
            && $this->countNullFabricIds() === 0
        ) {
            DB::statement('ALTER TABLE `projectx_quote_lines` MODIFY `fabric_id` BIGINT UNSIGNED NOT NULL');
        }
    }

    protected function isColumnNullable(string $table, string $column): bool
    {
        $databaseName = DB::getDatabaseName();

        $row = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first(['IS_NULLABLE']);

        return ! empty($row) && strtoupper((string) $row->IS_NULLABLE) === 'YES';
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

    protected function countNullFabricIds(): int
    {
        return (int) DB::table('projectx_quote_lines')
            ->whereNull('fabric_id')
            ->count();
    }
}
