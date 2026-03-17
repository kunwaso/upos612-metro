<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxQuotesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_quotes')) {
            return;
        }

        Schema::create('projectx_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->char('uuid', 36)->unique();
            $table->string('public_token', 64)->unique();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('location_id');
            $table->dateTime('expires_at');
            $table->string('currency', 50)->nullable();
            $table->string('incoterm', 50)->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedInteger('transaction_id')->nullable();
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->unsignedInteger('line_count')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');

            $table->index('business_id');
            $table->index('public_token');
            $table->index('expires_at');
            $table->index('contact_id');
            $table->index('location_id');
            $table->index('transaction_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_quotes');
    }
}
