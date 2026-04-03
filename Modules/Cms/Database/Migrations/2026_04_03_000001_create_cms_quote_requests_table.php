<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_quote_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('email');
            $table->string('phone');
            $table->string('company')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_quote_requests');
    }
};
