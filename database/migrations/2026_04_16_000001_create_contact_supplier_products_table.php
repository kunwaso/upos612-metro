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
        Schema::create('contact_supplier_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('contact_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->timestamps();

            $table->unique(
                ['business_id', 'contact_id', 'product_id'],
                'contact_supplier_products_business_contact_product_unique'
            );
            $table->index('business_id');
            $table->index(['business_id', 'contact_id'], 'contact_supplier_products_business_contact_index');
            $table->index(['business_id', 'product_id'], 'contact_supplier_products_business_product_index');

            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');
            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_supplier_products');
    }
};
