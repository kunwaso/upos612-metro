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
        Schema::create('contact_feeds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->integer('contact_id')->index();
            $table->string('provider', 50)->index();
            $table->string('title', 512);
            $table->text('snippet')->nullable();
            $table->text('canonical_url');
            $table->string('url_hash', 64)->index();
            $table->string('source_name')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('fetched_at')->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(
                ['business_id', 'contact_id', 'provider', 'url_hash'],
                'contact_feeds_business_contact_provider_hash_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_feeds');
    }
};
