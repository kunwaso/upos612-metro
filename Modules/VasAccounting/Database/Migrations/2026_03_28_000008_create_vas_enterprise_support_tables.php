<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasEnterpriseSupportTables extends Migration
{
    public function up()
    {
        $this->createDocumentApprovalsTable();
        $this->createDocumentAuditLogsTable();
        $this->createDocumentAttachmentsTable();
        $this->createReceivableAllocationsTable();
        $this->createPayableAllocationsTable();
        $this->createBankStatementImportsTable();
        $this->createBankStatementLinesTable();
        $this->createPayrollBatchesTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_payroll_batches');
        Schema::dropIfExists('vas_bank_statement_lines');
        Schema::dropIfExists('vas_bank_statement_imports');
        Schema::dropIfExists('vas_payable_allocations');
        Schema::dropIfExists('vas_receivable_allocations');
        Schema::dropIfExists('vas_document_attachments');
        Schema::dropIfExists('vas_document_audit_logs');
        Schema::dropIfExists('vas_document_approvals');
    }

    protected function createDocumentApprovalsTable(): void
    {
        if (Schema::hasTable('vas_document_approvals')) {
            return;
        }

        Schema::create('vas_document_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('entity_type', 120);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedInteger('step_no')->default(1);
            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('acted_by')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('acted_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'entity_type', 'entity_id']);
        });
    }

    protected function createDocumentAuditLogsTable(): void
    {
        if (Schema::hasTable('vas_document_audit_logs')) {
            return;
        }

        Schema::create('vas_document_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('entity_type', 120);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action', 60);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'entity_type', 'entity_id']);
        });
    }

    protected function createDocumentAttachmentsTable(): void
    {
        if (Schema::hasTable('vas_document_attachments')) {
            return;
        }

        Schema::create('vas_document_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('entity_type', 120);
            $table->unsignedBigInteger('entity_id');
            $table->string('attachment_name');
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'entity_type', 'entity_id']);
        });
    }

    protected function createReceivableAllocationsTable(): void
    {
        if (Schema::hasTable('vas_receivable_allocations')) {
            return;
        }

        Schema::create('vas_receivable_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->unsignedBigInteger('invoice_voucher_id')->nullable();
            $table->unsignedBigInteger('payment_voucher_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->date('allocation_date');
            $table->decimal('amount', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('invoice_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('payment_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }

    protected function createPayableAllocationsTable(): void
    {
        if (Schema::hasTable('vas_payable_allocations')) {
            return;
        }

        Schema::create('vas_payable_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->unsignedBigInteger('bill_voucher_id')->nullable();
            $table->unsignedBigInteger('payment_voucher_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->date('allocation_date');
            $table->decimal('amount', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('bill_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('payment_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }

    protected function createBankStatementImportsTable(): void
    {
        if (Schema::hasTable('vas_bank_statement_imports')) {
            return;
        }

        Schema::create('vas_bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('provider', 50)->default('manual');
            $table->string('reference_no', 120)->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('imported_by')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('vas_bank_accounts')->onDelete('set null');
            $table->foreign('imported_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    protected function createBankStatementLinesTable(): void
    {
        if (Schema::hasTable('vas_bank_statement_lines')) {
            return;
        }

        Schema::create('vas_bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('statement_import_id');
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('running_balance', 22, 4)->nullable();
            $table->string('match_status', 30)->default('unmatched');
            $table->unsignedBigInteger('matched_voucher_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('statement_import_id')->references('id')->on('vas_bank_statement_imports')->onDelete('cascade');
            $table->foreign('matched_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
        });
    }

    protected function createPayrollBatchesTable(): void
    {
        if (Schema::hasTable('vas_payroll_batches')) {
            return;
        }

        Schema::create('vas_payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('payroll_group_id')->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('reference_no', 120)->nullable();
            $table->date('payroll_month')->nullable();
            $table->decimal('gross_total', 22, 4)->default(0);
            $table->decimal('net_total', 22, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
        });
    }
}
