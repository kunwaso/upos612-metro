<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVasComplianceProfileColumnsToBusinessSettings extends Migration
{
    public function up(): void
    {
        Schema::table('vas_business_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_business_settings', 'compliance_standard')) {
                $table->string('compliance_standard', 50)->default('tt99_2025');
            }

            if (! Schema::hasColumn('vas_business_settings', 'compliance_effective_date')) {
                $table->date('compliance_effective_date')->default('2026-01-01');
            }

            if (! Schema::hasColumn('vas_business_settings', 'compliance_legacy_bridge_enabled')) {
                $table->boolean('compliance_legacy_bridge_enabled')->default(false);
            }

            if (! Schema::hasColumn('vas_business_settings', 'compliance_profile_version')) {
                $table->string('compliance_profile_version', 20)->default('2026.01');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vas_business_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vas_business_settings', 'compliance_profile_version')) {
                $table->dropColumn('compliance_profile_version');
            }

            if (Schema::hasColumn('vas_business_settings', 'compliance_legacy_bridge_enabled')) {
                $table->dropColumn('compliance_legacy_bridge_enabled');
            }

            if (Schema::hasColumn('vas_business_settings', 'compliance_effective_date')) {
                $table->dropColumn('compliance_effective_date');
            }

            if (Schema::hasColumn('vas_business_settings', 'compliance_standard')) {
                $table->dropColumn('compliance_standard');
            }
        });
    }
}

