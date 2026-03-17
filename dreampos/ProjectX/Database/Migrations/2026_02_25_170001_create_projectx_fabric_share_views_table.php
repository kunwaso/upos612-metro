<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxFabricShareViewsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_fabric_share_views')) {
            return;
        }

        Schema::create('projectx_fabric_share_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fabric_id');
            $table->timestamp('viewed_at');
            $table->string('ip_address', 45)->nullable();

            $table->foreign('fabric_id')
                ->references('id')
                ->on('projectx_fabrics')
                ->onDelete('cascade');

            $table->index(['fabric_id', 'viewed_at'], 'projectx_fabric_share_views_lookup_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_fabric_share_views');
    }
}