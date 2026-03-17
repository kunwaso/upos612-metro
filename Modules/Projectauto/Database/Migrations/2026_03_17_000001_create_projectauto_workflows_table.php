<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projectauto_workflows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->string('trigger_type', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('draft_graph')->nullable();
            $table->json('published_graph')->nullable();
            $table->json('last_validation_errors')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'is_active'], 'projectauto_workflows_business_active_idx');
            $table->index(['business_id', 'trigger_type'], 'projectauto_workflows_trigger_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectauto_workflows');
    }
};
