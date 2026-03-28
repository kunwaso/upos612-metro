<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVasCutoverSettingsToBusinessSettings extends Migration
{
    public function up()
    {
        Schema::table('vas_business_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_business_settings', 'cutover_settings')) {
                $table->json('cutover_settings')->nullable()->after('budget_settings');
            }

            if (! Schema::hasColumn('vas_business_settings', 'rollout_settings')) {
                $table->json('rollout_settings')->nullable()->after('cutover_settings');
            }
        });
    }

    public function down()
    {
        Schema::table('vas_business_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vas_business_settings', 'rollout_settings')) {
                $table->dropColumn('rollout_settings');
            }

            if (Schema::hasColumn('vas_business_settings', 'cutover_settings')) {
                $table->dropColumn('cutover_settings');
            }
        });
    }
}
