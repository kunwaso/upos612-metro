<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductActivityLogTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('product_activity_log')) {
            return;
        }

        Schema::create('product_activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('product_id');
            $table->string('action_type', 100);
            $table->text('description');
            $table->unsignedInteger('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('business_id');
            $table->index('product_id');
            $table->index('action_type');
            $table->index('created_at');
            $table->index(['business_id', 'product_id', 'created_at'], 'product_activity_log_lookup_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_activity_log');
    }
}
