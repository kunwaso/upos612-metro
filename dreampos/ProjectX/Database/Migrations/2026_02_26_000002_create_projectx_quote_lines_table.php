<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxQuoteLinesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_quote_lines')) {
            return;
        }

        Schema::create('projectx_quote_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('fabric_id');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->json('fabric_snapshot');
            $table->json('costing_input');
            $table->json('costing_breakdown');
            $table->timestamps();

            $table->foreign('quote_id')->references('id')->on('projectx_quotes')->onDelete('cascade');
            $table->foreign('fabric_id')->references('id')->on('projectx_fabrics')->onDelete('cascade');

            $table->index('quote_id');
            $table->index('fabric_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_quote_lines');
    }
}
