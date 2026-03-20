<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTranslationColumnsToEssentialsTranscriptsTable extends Migration
{
    public function up()
    {
        Schema::table('essentials_transcripts', function (Blueprint $table) {
            if (! Schema::hasColumn('essentials_transcripts', 'source_language')) {
                $table->string('source_language', 20)->nullable()->after('title');
            }

            if (! Schema::hasColumn('essentials_transcripts', 'target_language')) {
                $table->string('target_language', 20)->nullable()->after('source_language');
            }

            if (! Schema::hasColumn('essentials_transcripts', 'translated_text')) {
                $table->longText('translated_text')->nullable()->after('transcript');
            }
        });
    }

    public function down()
    {
        Schema::table('essentials_transcripts', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('essentials_transcripts', 'source_language')) {
                $columns[] = 'source_language';
            }
            if (Schema::hasColumn('essentials_transcripts', 'target_language')) {
                $columns[] = 'target_language';
            }
            if (Schema::hasColumn('essentials_transcripts', 'translated_text')) {
                $columns[] = 'translated_text';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
