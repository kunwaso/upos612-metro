<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVasUiSettingsToBusinessSettings extends Migration
{
    public function up()
    {
        Schema::table('vas_business_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_business_settings', 'ui_settings')) {
                $table->json('ui_settings')->nullable()->after('rollout_settings');
            }
        });
    }

    public function down()
    {
        Schema::table('vas_business_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vas_business_settings', 'ui_settings')) {
                $table->dropColumn('ui_settings');
            }
        });
    }
}
