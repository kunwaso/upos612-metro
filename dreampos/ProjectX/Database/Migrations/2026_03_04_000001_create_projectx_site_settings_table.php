<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxSiteSettingsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('projectx_site_settings')) {
            Schema::create('projectx_site_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable();
                $table->string('key', 100);
                $table->text('value')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'key']);
                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('projectx_site_settings');
    }
}
