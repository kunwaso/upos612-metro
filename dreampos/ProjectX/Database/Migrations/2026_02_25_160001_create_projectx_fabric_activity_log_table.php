<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxFabricActivityLogTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_fabric_activity_log')) {
            return;
        }

        Schema::create('projectx_fabric_activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('fabric_id');
            $table->string('action_type', 100);
            $table->text('description');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');

            $table->foreign('fabric_id')
                ->references('id')
                ->on('projectx_fabrics')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('business_id');
            $table->index('fabric_id');
            $table->index('action_type');
            $table->index('created_at');
            $table->index(['business_id', 'fabric_id', 'created_at'], 'projectx_fabric_activity_log_lookup_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_fabric_activity_log');
    }
}
