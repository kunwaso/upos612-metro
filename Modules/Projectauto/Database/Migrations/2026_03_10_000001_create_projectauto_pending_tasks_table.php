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
        Schema::create('projectauto_pending_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('task_type', 50);
            $table->enum('status', ['pending', 'approved', 'rejected', 'failed'])->default('pending');
            $table->json('payload');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 191)->nullable();
            $table->unsignedInteger('source_transaction_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->unsignedInteger('rejected_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('last_error')->nullable();
            $table->string('result_model_type', 191)->nullable();
            $table->unsignedBigInteger('result_model_id')->nullable();
            $table->text('rejection_notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status'], 'projectauto_tasks_business_status_idx');
            $table->index(['business_id', 'task_type'], 'projectauto_tasks_business_type_idx');
            $table->index(['business_id', 'source_transaction_id'], 'projectauto_tasks_source_txn_idx');
            $table->index(['business_id', 'created_at'], 'projectauto_tasks_created_idx');
            $table->unique(['business_id', 'idempotency_key'], 'projectauto_tasks_business_idempotency_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projectauto_pending_tasks');
    }
};
