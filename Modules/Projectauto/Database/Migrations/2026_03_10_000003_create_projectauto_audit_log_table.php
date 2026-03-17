<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projectauto_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('pending_task_id')->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('action', 100);
            $table->string('status_before', 50)->nullable();
            $table->string('status_after', 50)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at'], 'projectauto_audit_business_created_idx');
            $table->index(['pending_task_id'], 'projectauto_audit_task_idx');
            $table->index(['rule_id'], 'projectauto_audit_rule_idx');
            $table->index(['business_id', 'action'], 'projectauto_audit_action_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projectauto_audit_log');
    }
};
