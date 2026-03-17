<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxTrimShareViewsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_trim_share_views')) {
            return;
        }

        Schema::create('projectx_trim_share_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trim_id');
            $table->timestamp('viewed_at');
            $table->string('ip_address', 45)->nullable();

            $table->foreign('trim_id')
                ->references('id')
                ->on('projectx_trims')
                ->onDelete('cascade');

            $table->index(['trim_id', 'viewed_at'], 'projectx_trim_share_views_lookup_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_trim_share_views');
    }
}
