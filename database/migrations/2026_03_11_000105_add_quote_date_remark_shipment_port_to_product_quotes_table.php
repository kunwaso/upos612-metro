<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteDateRemarkShipmentPortToProductQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('product_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('product_quotes', 'quote_date')) {
                $table->date('quote_date')->nullable()->after('location_id');
            }
            if (! Schema::hasColumn('product_quotes', 'remark')) {
                $table->text('remark')->nullable()->after('line_count');
            }
            if (! Schema::hasColumn('product_quotes', 'shipment_port')) {
                $table->string('shipment_port', 255)->nullable()->after('remark');
            }
        });
    }

    public function down()
    {
        Schema::table('product_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('product_quotes', 'quote_date')) {
                $table->dropColumn('quote_date');
            }
            if (Schema::hasColumn('product_quotes', 'remark')) {
                $table->dropColumn('remark');
            }
            if (Schema::hasColumn('product_quotes', 'shipment_port')) {
                $table->dropColumn('shipment_port');
            }
        });
    }
}
