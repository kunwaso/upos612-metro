<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxQuoteSettingsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_quote_settings')) {
            return;
        }

        Schema::create('projectx_quote_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->unique();
            $table->unsignedInteger('default_currency_id')->nullable();
            $table->json('incoterm_options')->nullable();
            $table->json('purchase_uom_options')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('default_currency_id')->references('id')->on('currencies')->onDelete('set null');

            $table->index('default_currency_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_quote_settings');
    }
}

