<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxTrimCategoriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_trim_categories')) {
            return;
        }

        Schema::create('projectx_trim_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('name');
            $table->integer('sort_order')->nullable();
            $table->string('category_group')->nullable();
            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');

            $table->index('business_id');
            $table->index('category_group');
            $table->unique(['business_id', 'name'], 'projectx_trim_categories_business_name_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_trim_categories');
    }
}
