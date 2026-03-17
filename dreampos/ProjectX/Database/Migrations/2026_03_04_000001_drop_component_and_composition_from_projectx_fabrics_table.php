<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projectx_fabrics', function (Blueprint $table) {
            if (Schema::hasColumn('projectx_fabrics', 'component')) {
                $table->dropColumn('component');
            }
            if (Schema::hasColumn('projectx_fabrics', 'composition')) {
                $table->dropColumn('composition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projectx_fabrics', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_fabrics', 'component')) {
                $table->string('component')->nullable()->after('status');
            }
            if (! Schema::hasColumn('projectx_fabrics', 'composition')) {
                $table->string('composition')->nullable()->after('description');
            }
        });
    }
};
