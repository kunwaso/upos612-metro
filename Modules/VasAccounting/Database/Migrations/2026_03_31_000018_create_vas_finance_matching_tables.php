<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasFinanceMatchingTables extends Migration
{
    public function up()
    {
        $this->createFinanceMatchRunsTable();
        $this->createFinanceMatchRunLinesTable();
        $this->createFinanceMatchExceptionsTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_fin_match_exceptions');
        Schema::dropIfExists('vas_fin_match_run_lines');
        Schema::dropIfExists('vas_fin_match_runs');
    }

    protected function createFinanceMatchRunsTable(): void
    {
        if (Schema::hasTable('vas_fin_match_runs')) {
            return;
        }

        Schema::create('vas_fin_match_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->string('match_type', 40)->default('supplier_invoice');
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('total_line_count')->default(0);
            $table->unsignedInteger('matched_line_count')->default(0);
            $table->unsignedInteger('blocking_exception_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->json('parent_document_ids')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->unsignedInteger('matched_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('matched_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['document_id', 'id'], 'vas_fin_match_runs_document_id_index');
            $table->index(['business_id', 'status', 'matched_at'], 'vas_fin_match_runs_business_status_index');
        });
    }

    protected function createFinanceMatchRunLinesTable(): void
    {
        if (Schema::hasTable('vas_fin_match_run_lines')) {
            return;
        }

        Schema::create('vas_fin_match_run_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('match_run_id')->constrained('vas_fin_match_runs')->cascadeOnDelete();
            $table->foreignId('document_line_id')->constrained('vas_fin_document_lines')->cascadeOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->foreignId('source_document_line_id')->nullable()->constrained('vas_fin_document_lines')->nullOnDelete();
            $table->string('source_document_type', 60)->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('matched_quantity', 22, 4)->default(0);
            $table->decimal('matched_amount', 22, 4)->default(0);
            $table->decimal('matched_tax_amount', 22, 4)->default(0);
            $table->decimal('variance_quantity', 22, 4)->default(0);
            $table->decimal('variance_amount', 22, 4)->default(0);
            $table->decimal('variance_tax_amount', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['match_run_id', 'document_line_id'], 'vas_fin_match_run_lines_match_run_line_unique');
            $table->index(['business_id', 'source_document_id'], 'vas_fin_match_run_lines_source_document_index');
            $table->index(['business_id', 'status'], 'vas_fin_match_run_lines_business_status_index');
        });
    }

    protected function createFinanceMatchExceptionsTable(): void
    {
        if (Schema::hasTable('vas_fin_match_exceptions')) {
            return;
        }

        Schema::create('vas_fin_match_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('match_run_id')->constrained('vas_fin_match_runs')->cascadeOnDelete();
            $table->foreignId('document_line_id')->nullable()->constrained('vas_fin_document_lines')->nullOnDelete();
            $table->string('severity', 20)->default('blocking');
            $table->string('code', 60);
            $table->string('status', 30)->default('open');
            $table->text('message');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['match_run_id', 'severity'], 'vas_fin_match_exceptions_run_severity_index');
            $table->index(['business_id', 'document_id', 'status'], 'vas_fin_match_exceptions_document_status_index');
        });
    }
}
