<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('storage_slots', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('category_id');
            $table->string('row', 50);
            $table->string('position', 50);
            $table->string('slot_code', 50)->nullable();
            $table->unsignedInteger('max_capacity')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'location_id']);
            $table->index(['business_id', 'category_id']);
            $table->unique(['business_id', 'location_id', 'category_id', 'row', 'position'], 'storage_slots_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('storage_slots');
    }
};
