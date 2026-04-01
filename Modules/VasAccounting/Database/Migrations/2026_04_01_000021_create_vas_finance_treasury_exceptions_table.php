<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasFinanceTreasuryExceptionsTable extends Migration
{
    public function up()
    {
        $this->createFinanceTreasuryExceptionsTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_fin_treasury_exceptions');
    }

    protected function createFinanceTreasuryExceptionsTable(): void
    {
        if (Schema::hasTable('vas_fin_treasury_exceptions')) {
            return;
        }

        Schema::create('vas_fin_treasury_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('statement_line_id')->constrained('vas_bank_statement_lines')->cascadeOnDelete();
            $table->foreignId('recommended_document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->foreignId('reconciliation_id')->nullable()->constrained('vas_fin_treasury_reconciliations')->nullOnDelete();
            $table->string('status', 30)->default('open');
            $table->string('severity', 20)->default('warning');
            $table->string('exception_code', 60);
            $table->decimal('top_match_score', 8, 4)->default(0);
            $table->text('message')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['statement_line_id'], 'vas_fin_treasury_exceptions_statement_unique');
            $table->index(['business_id', 'status', 'severity'], 'vas_fin_treasury_exceptions_status_severity_index');
        });
    }
}
