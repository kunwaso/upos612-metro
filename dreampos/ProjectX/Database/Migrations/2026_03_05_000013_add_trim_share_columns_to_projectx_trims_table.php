<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrimShareColumnsToProjectxTrimsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_trims')) {
            return;
        }

        Schema::table('projectx_trims', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_trims', 'share_enabled')) {
                $table->boolean('share_enabled')->default(false)->index();
            }

            if (! Schema::hasColumn('projectx_trims', 'share_token')) {
                $table->string('share_token', 64)->nullable()->unique();
            }

            if (! Schema::hasColumn('projectx_trims', 'share_password_hash')) {
                $table->string('share_password_hash')->nullable();
            }

            if (! Schema::hasColumn('projectx_trims', 'share_expires_at')) {
                $table->timestamp('share_expires_at')->nullable();
            }

            if (! Schema::hasColumn('projectx_trims', 'share_rate_limit_per_day')) {
                $table->unsignedInteger('share_rate_limit_per_day')->nullable();
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('projectx_trims')) {
            return;
        }

        Schema::table('projectx_trims', function (Blueprint $table) {
            $columns = [
                'share_enabled',
                'share_token',
                'share_password_hash',
                'share_expires_at',
                'share_rate_limit_per_day',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('projectx_trims', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
