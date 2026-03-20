<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_racks', function (Blueprint $table) {
            $table->unsignedInteger('slot_id')->nullable()->after('position');
            $table->index('slot_id');
        });
    }

    public function down()
    {
        Schema::table('product_racks', function (Blueprint $table) {
            $table->dropIndex(['slot_id']);
            $table->dropColumn('slot_id');
        });
    }
};
