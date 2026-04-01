<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasFinanceTreasuryReconciliationsTable extends Migration
{
    public function up()
    {
        $this->createFinanceTreasuryReconciliationsTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_fin_treasury_reconciliations');
    }

    protected function createFinanceTreasuryReconciliationsTable(): void
    {
        if (Schema::hasTable('vas_fin_treasury_reconciliations')) {
            return;
        }

        Schema::create('vas_fin_treasury_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('statement_line_id')->constrained('vas_bank_statement_lines')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('open_item_id')->nullable()->constrained('vas_fin_open_items')->nullOnDelete();
            $table->foreignId('accounting_event_id')->nullable()->constrained('vas_fin_accounting_events')->nullOnDelete();
            $table->string('reconciliation_type', 30)->default('statement_match');
            $table->string('direction', 20)->nullable();
            $table->string('status', 30)->default('active');
            $table->decimal('match_confidence', 8, 4)->default(0);
            $table->decimal('statement_amount', 22, 4)->default(0);
            $table->decimal('document_amount', 22, 4)->default(0);
            $table->decimal('matched_amount', 22, 4)->default(0);
            $table->string('currency_code', 10)->default('VND');
            $table->text('match_notes')->nullable();
            $table->unsignedInteger('reconciled_by')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('reconciled_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['business_id', 'status', 'reconciled_at'], 'vas_fin_treasury_reconciliations_status_index');
            $table->index(['statement_line_id', 'status'], 'vas_fin_treasury_reconciliations_statement_status_index');
            $table->index(['document_id', 'status'], 'vas_fin_treasury_reconciliations_document_status_index');
        });
    }
}
