<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxFabricsTable extends Migration
{
    public function up()
    {
        Schema::create('projectx_fabrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->string('name');
            $table->string('status')->default('draft');
            $table->string('component')->nullable();
            $table->string('fiber')->nullable();
            $table->decimal('purchase_price', 22, 4)->default(0);
            $table->decimal('sale_price', 22, 4)->default(0);

            $table->unsignedInteger('supplier_contact_id')->nullable();
            $table->foreign('supplier_contact_id')->references('id')->on('contacts')->onDelete('set null');

            $table->string('image_path')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->index('business_id');
            $table->index('status');
            $table->index('supplier_contact_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_fabrics');
    }
}
