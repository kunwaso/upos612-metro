<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterMillPatternColorToJsonArray extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('projectx_fabrics', 'mill_pattern_color')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE projectx_fabrics MODIFY mill_pattern_color TEXT NULL');
        } else {
            Schema::table('projectx_fabrics', function (Blueprint $table) {
                $table->text('mill_pattern_color')->nullable()->change();
            });
        }

        // Convert existing non-empty string values to JSON array of one element
        $rows = DB::table('projectx_fabrics')
            ->whereNotNull('mill_pattern_color')
            ->where('mill_pattern_color', '!=', '')
            ->get(['id', 'mill_pattern_color']);

        foreach ($rows as $row) {
            $value = $row->mill_pattern_color;
            if ($value === null || $value === '') {
                continue;
            }
            // If already JSON array, leave as is
            $trimmed = trim($value);
            if ($trimmed !== '' && $trimmed[0] === '[') {
                continue;
            }
            DB::table('projectx_fabrics')
                ->where('id', $row->id)
                ->update(['mill_pattern_color' => json_encode([$value])]);
        }
    }

    public function down()
    {
        if (! Schema::hasColumn('projectx_fabrics', 'mill_pattern_color')) {
            return;
        }

        // Convert JSON arrays back to single string (first element)
        $rows = DB::table('projectx_fabrics')
            ->whereNotNull('mill_pattern_color')
            ->get(['id', 'mill_pattern_color']);

        foreach ($rows as $row) {
            $decoded = json_decode($row->mill_pattern_color, true);
            $single = is_array($decoded) && count($decoded) > 0
                ? (string) $decoded[0]
                : $row->mill_pattern_color;
            DB::table('projectx_fabrics')
                ->where('id', $row->id)
                ->update(['mill_pattern_color' => $single]);
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE projectx_fabrics MODIFY mill_pattern_color VARCHAR(255) NULL');
        } else {
            Schema::table('projectx_fabrics', function (Blueprint $table) {
                $table->string('mill_pattern_color')->nullable()->change();
            });
        }
    }
}
