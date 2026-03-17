<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicLinkPasswordToProjectxQuotesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_quotes')) {
            return;
        }

        Schema::table('projectx_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_quotes', 'public_link_password')) {
                $table->string('public_link_password')->nullable()->after('public_token');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('projectx_quotes')) {
            return;
        }

        Schema::table('projectx_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('projectx_quotes', 'public_link_password')) {
                $table->dropColumn('public_link_password');
            }
        });
    }
}
