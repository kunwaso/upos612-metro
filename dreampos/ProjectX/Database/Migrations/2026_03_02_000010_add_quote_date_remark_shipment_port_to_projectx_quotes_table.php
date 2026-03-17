<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteDateRemarkShipmentPortToProjectxQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('projectx_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_quotes', 'quote_date')) {
                $table->date('quote_date')->nullable()->after('location_id');
            }
            if (! Schema::hasColumn('projectx_quotes', 'remark')) {
                $table->text('remark')->nullable()->after('line_count');
            }
            if (! Schema::hasColumn('projectx_quotes', 'shipment_port')) {
                $table->string('shipment_port', 255)->nullable()->after('remark');
            }
        });
    }

    public function down()
    {
        Schema::table('projectx_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('projectx_quotes', 'quote_date')) {
                $table->dropColumn('quote_date');
            }
            if (Schema::hasColumn('projectx_quotes', 'remark')) {
                $table->dropColumn('remark');
            }
            if (Schema::hasColumn('projectx_quotes', 'shipment_port')) {
                $table->dropColumn('shipment_port');
            }
        });
    }
}
