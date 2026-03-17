<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('projectauto_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('projectauto_rules', 'workflow_id')) {
                $table->unsignedBigInteger('workflow_id')->nullable()->after('business_id');
            }

            if (! Schema::hasColumn('projectauto_rules', 'workflow_node_id')) {
                $table->string('workflow_node_id', 100)->nullable()->after('workflow_id');
            }

            if (! Schema::hasColumn('projectauto_rules', 'workflow_branch')) {
                $table->string('workflow_branch', 20)->nullable()->after('workflow_node_id');
            }
        });

        Schema::table('projectauto_rules', function (Blueprint $table) {
            $table->index(['business_id', 'workflow_id'], 'projectauto_rules_workflow_idx');
        });
    }

    public function down()
    {
        Schema::table('projectauto_rules', function (Blueprint $table) {
            if (Schema::hasColumn('projectauto_rules', 'workflow_id')) {
                $table->dropIndex('projectauto_rules_workflow_idx');
                $table->dropColumn(['workflow_id', 'workflow_node_id', 'workflow_branch']);
            }
        });
    }
};
