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
        Schema::create('projectauto_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('name', 191);
            $table->string('trigger_type', 100);
            $table->string('task_type', 50);
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('stop_on_match')->default(false);
            $table->json('conditions')->nullable();
            $table->json('payload_template')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'trigger_type', 'is_active'], 'projectauto_rules_trigger_idx');
            $table->index(['business_id', 'priority', 'id'], 'projectauto_rules_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projectauto_rules');
    }
};
