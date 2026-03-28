<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasPhaseSevenOperationTables extends Migration
{
    public function up()
    {
        $this->alterReportSnapshotsTable();
        $this->createCloseChecklistsTable();
        $this->createIntegrationRunsTable();
        $this->createIntegrationWebhooksTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_integration_webhooks');
        Schema::dropIfExists('vas_integration_runs');
        Schema::dropIfExists('vas_close_checklists');

        if (Schema::hasTable('vas_report_snapshots')) {
            Schema::table('vas_report_snapshots', function (Blueprint $table) {
                if (Schema::hasColumn('vas_report_snapshots', 'snapshot_name')) {
                    $table->dropColumn('snapshot_name');
                }
                if (Schema::hasColumn('vas_report_snapshots', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('vas_report_snapshots', 'error_message')) {
                    $table->dropColumn('error_message');
                }
            });
        }
    }

    protected function alterReportSnapshotsTable(): void
    {
        if (! Schema::hasTable('vas_report_snapshots')) {
            return;
        }

        Schema::table('vas_report_snapshots', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_report_snapshots', 'snapshot_name')) {
                $table->string('snapshot_name', 150)->nullable()->after('report_key');
            }
            if (! Schema::hasColumn('vas_report_snapshots', 'status')) {
                $table->string('status', 30)->default('queued')->after('snapshot_name');
            }
            if (! Schema::hasColumn('vas_report_snapshots', 'error_message')) {
                $table->text('error_message')->nullable()->after('payload');
            }
        });
    }

    protected function createCloseChecklistsTable(): void
    {
        if (Schema::hasTable('vas_close_checklists')) {
            return;
        }

        Schema::create('vas_close_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_period_id')->constrained('vas_accounting_periods')->cascadeOnDelete();
            $table->string('checklist_key', 80);
            $table->string('title', 150);
            $table->string('status', 30)->default('pending');
            $table->boolean('is_required')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('completed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'accounting_period_id', 'checklist_key'], 'vas_close_checklists_unique');
        });
    }

    protected function createIntegrationRunsTable(): void
    {
        if (Schema::hasTable('vas_integration_runs')) {
            return;
        }

        Schema::create('vas_integration_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('run_type', 60);
            $table->string('provider', 60)->nullable();
            $table->string('action', 80);
            $table->string('status', 30)->default('queued');
            $table->unsignedInteger('requested_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'run_type', 'status'], 'vas_integration_runs_status_idx');
        });
    }

    protected function createIntegrationWebhooksTable(): void
    {
        if (Schema::hasTable('vas_integration_webhooks')) {
            return;
        }

        Schema::create('vas_integration_webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->string('provider', 60);
            $table->string('event_key', 120)->nullable();
            $table->string('external_reference', 120)->nullable();
            $table->string('status', 30)->default('received');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['provider', 'status'], 'vas_integration_webhooks_status_idx');
        });
    }
}
