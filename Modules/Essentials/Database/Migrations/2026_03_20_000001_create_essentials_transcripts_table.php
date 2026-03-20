<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEssentialsTranscriptsTable extends Migration
{
    public function up()
    {
        Schema::create('essentials_transcripts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->integer('user_id')->index();
            $table->string('title')->nullable();
            $table->longText('transcript');
            $table->string('audio_filename')->nullable();
            $table->enum('source', ['upload', 'live'])->default('upload');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('essentials_transcripts');
    }
}
