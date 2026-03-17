<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductQuoteLinesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('product_quote_lines')) {
            return;
        }

        Schema::create('product_quote_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->unsignedInteger('product_id');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->json('product_snapshot');
            $table->json('costing_input');
            $table->json('costing_breakdown');
            $table->timestamps();

            $table->foreign('quote_id')->references('id')->on('product_quotes')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');

            $table->index('quote_id');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_quote_lines');
    }
}
