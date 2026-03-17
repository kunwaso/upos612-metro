<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductVariationLinksToProjectxFabricsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_fabrics')) {
            return;
        }

        Schema::table('projectx_fabrics', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_fabrics', 'product_id')) {
                $table->unsignedInteger('product_id')->nullable()->after('business_id');
                $table->unique('product_id');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            }

            if (! Schema::hasColumn('projectx_fabrics', 'variation_id')) {
                $table->unsignedInteger('variation_id')->nullable()->after('product_id');
                $table->unique('variation_id');
                $table->foreign('variation_id')->references('id')->on('variations')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('projectx_fabrics')) {
            return;
        }

        Schema::table('projectx_fabrics', function (Blueprint $table) {
            if (Schema::hasColumn('projectx_fabrics', 'variation_id')) {
                $table->dropForeign(['variation_id']);
                $table->dropUnique(['variation_id']);
                $table->dropColumn('variation_id');
            }

            if (Schema::hasColumn('projectx_fabrics', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropUnique(['product_id']);
                $table->dropColumn('product_id');
            }
        });
    }
}
