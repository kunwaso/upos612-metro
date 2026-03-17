<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicLinkPasswordToProductQuotesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('product_quotes')) {
            return;
        }

        Schema::table('product_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('product_quotes', 'public_link_password')) {
                $table->string('public_link_password')->nullable()->after('public_token');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('product_quotes')) {
            return;
        }

        Schema::table('product_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('product_quotes', 'public_link_password')) {
                $table->dropColumn('public_link_password');
            }
        });
    }
}
