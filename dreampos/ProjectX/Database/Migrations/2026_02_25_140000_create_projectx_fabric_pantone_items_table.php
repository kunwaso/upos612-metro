<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxFabricPantoneItemsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_fabric_pantone_items')) {
            return;
        }

        Schema::create('projectx_fabric_pantone_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fabric_id');
            $table->string('pantone_code', 50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('fabric_id')
                ->references('id')
                ->on('projectx_fabrics')
                ->onDelete('cascade');

            $table->index('fabric_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_fabric_pantone_items');
    }
}
