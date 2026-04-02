<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcurementControlsToVasFinMatchExceptionsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('vas_fin_match_exceptions')) {
            return;
        }

        Schema::table('vas_fin_match_exceptions', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_fin_match_exceptions', 'owner_id')) {
                $table->unsignedInteger('owner_id')->nullable()->after('status');
                $table->timestamp('owner_assigned_at')->nullable()->after('owner_id');
                $table->unsignedInteger('reviewed_by')->nullable()->after('owner_assigned_at');
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                $table->unsignedInteger('resolved_by')->nullable()->after('reviewed_at');
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
                $table->text('resolution_note')->nullable()->after('resolved_at');

                $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['business_id', 'status', 'owner_id'], 'vas_fin_match_ex_owner_status_index');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('vas_fin_match_exceptions')) {
            return;
        }

        Schema::table('vas_fin_match_exceptions', function (Blueprint $table) {
            if (Schema::hasColumn('vas_fin_match_exceptions', 'owner_id')) {
                $table->dropForeign(['owner_id']);
                $table->dropForeign(['reviewed_by']);
                $table->dropForeign(['resolved_by']);
                $table->dropIndex('vas_fin_match_ex_owner_status_index');
                $table->dropColumn([
                    'owner_id',
                    'owner_assigned_at',
                    'reviewed_by',
                    'reviewed_at',
                    'resolved_by',
                    'resolved_at',
                    'resolution_note',
                ]);
            }
        });
    }
}
