<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasFinanceCoreTables extends Migration
{
    public function up()
    {
        $this->createFinanceDocumentsTable();
        $this->createFinanceDocumentLinesTable();
        $this->createFinanceDocumentStatusHistoryTable();
        $this->createFinanceDocumentLinksTable();
        $this->createFinanceApprovalInstancesTable();
        $this->createFinanceApprovalStepsTable();
        $this->createFinancePostingRuleSetsTable();
        $this->createFinancePostingRuleLinesTable();
        $this->createFinanceAccountingEventsTable();
        $this->createFinanceAccountingEventLinesTable();
        $this->createFinanceJournalEntriesTable();
        $this->createFinanceJournalEntryLinesTable();
        $this->createFinanceTraceLinksTable();
        $this->createFinanceAuditEventsTable();
        $this->createFinanceDuplicateChecksTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_fin_duplicate_checks');
        Schema::dropIfExists('vas_fin_audit_events');
        Schema::dropIfExists('vas_fin_trace_links');
        Schema::dropIfExists('vas_fin_journal_entry_lines');
        Schema::dropIfExists('vas_fin_journal_entries');
        Schema::dropIfExists('vas_fin_accounting_event_lines');
        Schema::dropIfExists('vas_fin_accounting_events');
        Schema::dropIfExists('vas_fin_posting_rule_lines');
        Schema::dropIfExists('vas_fin_posting_rule_sets');
        Schema::dropIfExists('vas_fin_approval_steps');
        Schema::dropIfExists('vas_fin_approval_instances');
        Schema::dropIfExists('vas_fin_document_links');
        Schema::dropIfExists('vas_fin_document_status_history');
        Schema::dropIfExists('vas_fin_document_lines');
        Schema::dropIfExists('vas_fin_documents');
    }

    protected function createFinanceDocumentsTable(): void
    {
        if (Schema::hasTable('vas_fin_documents')) {
            return;
        }

        Schema::create('vas_fin_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_period_id')->nullable()->constrained('vas_accounting_periods')->nullOnDelete();
            $table->string('document_family', 40);
            $table->string('document_type', 60);
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('document_no', 120);
            $table->string('external_reference', 120)->nullable();
            $table->string('counterparty_type', 30)->nullable();
            $table->unsignedInteger('counterparty_id')->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('currency_code', 10)->default('VND');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->date('document_date');
            $table->date('posting_date')->nullable();
            $table->string('workflow_status', 40)->default('draft');
            $table->string('accounting_status', 40)->default('not_ready');
            $table->decimal('gross_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('net_amount', 22, 4)->default(0);
            $table->decimal('open_amount', 22, 4)->default(0);
            $table->foreignId('reversal_document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('submitted_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('cancelled_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['business_id', 'document_family', 'document_no'], 'vas_fin_documents_business_family_no_unique');
            $table->index(['business_id', 'document_family', 'workflow_status', 'document_date'], 'vas_fin_documents_family_status_date_index');
            $table->index(['business_id', 'document_type', 'accounting_status'], 'vas_fin_documents_type_accounting_status_index');
            $table->index(['business_id', 'source_type', 'source_id'], 'vas_fin_documents_source_index');
        });
    }

    protected function createFinanceDocumentLinesTable(): void
    {
        if (Schema::hasTable('vas_fin_document_lines')) {
            return;
        }

        Schema::create('vas_fin_document_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('line_type', 40)->default('standard');
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->foreignId('tax_code_id')->nullable()->constrained('vas_tax_codes')->nullOnDelete();
            $table->foreignId('account_hint_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('debit_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('credit_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('tax_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->string('source_line_reference', 120)->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('gross_amount', 22, 4)->default(0);
            $table->json('dimensions')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            $table->unique(['document_id', 'line_no'], 'vas_fin_document_lines_document_line_no_unique');
            $table->index(['business_id', 'product_id', 'business_location_id'], 'vas_fin_document_lines_product_location_index');
        });
    }

    protected function createFinanceDocumentStatusHistoryTable(): void
    {
        if (Schema::hasTable('vas_fin_document_status_history')) {
            return;
        }

        Schema::create('vas_fin_document_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->string('event_name', 40);
            $table->string('from_workflow_status', 40)->nullable();
            $table->string('to_workflow_status', 40)->nullable();
            $table->string('from_accounting_status', 40)->nullable();
            $table->string('to_accounting_status', 40)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedInteger('acted_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('acted_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['document_id', 'acted_at'], 'vas_fin_document_status_history_document_acted_index');
        });
    }

    protected function createFinanceDocumentLinksTable(): void
    {
        if (Schema::hasTable('vas_fin_document_links')) {
            return;
        }

        Schema::create('vas_fin_document_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('parent_document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('child_document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->string('link_type', 40);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['parent_document_id', 'child_document_id', 'link_type'], 'vas_fin_document_links_unique');
            $table->index(['business_id', 'link_type'], 'vas_fin_document_links_business_type_index');
        });
    }

    protected function createFinanceApprovalInstancesTable(): void
    {
        if (Schema::hasTable('vas_fin_approval_instances')) {
            return;
        }

        Schema::create('vas_fin_approval_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->string('policy_code', 80)->nullable();
            $table->string('status', 30)->default('not_started');
            $table->unsignedInteger('current_step_no')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['business_id', 'status'], 'vas_fin_approval_instances_business_status_index');
        });
    }

    protected function createFinanceApprovalStepsTable(): void
    {
        if (Schema::hasTable('vas_fin_approval_steps')) {
            return;
        }

        Schema::create('vas_fin_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('approval_instance_id')->constrained('vas_fin_approval_instances')->cascadeOnDelete();
            $table->unsignedInteger('step_no')->default(1);
            $table->unsignedInteger('approver_user_id')->nullable();
            $table->string('approver_role', 80)->nullable();
            $table->string('permission_code', 120)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('decision', 30)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('approver_user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['approval_instance_id', 'step_no'], 'vas_fin_approval_steps_instance_step_unique');
            $table->index(['business_id', 'status'], 'vas_fin_approval_steps_business_status_index');
        });
    }

    protected function createFinancePostingRuleSetsTable(): void
    {
        if (Schema::hasTable('vas_fin_posting_rule_sets')) {
            return;
        }

        Schema::create('vas_fin_posting_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('rule_code', 80);
            $table->string('rule_name');
            $table->unsignedInteger('version_no')->default(1);
            $table->string('document_family', 40);
            $table->string('document_type', 60)->nullable();
            $table->string('event_type', 40);
            $table->unsignedInteger('business_location_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->unique(['business_id', 'rule_code', 'version_no'], 'vas_fin_posting_rule_sets_business_code_version_unique');
            $table->index(['business_id', 'document_family', 'event_type', 'is_active'], 'vas_fin_posting_rule_sets_resolve_index');
        });
    }

    protected function createFinancePostingRuleLinesTable(): void
    {
        if (Schema::hasTable('vas_fin_posting_rule_lines')) {
            return;
        }

        Schema::create('vas_fin_posting_rule_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('posting_rule_set_id')->constrained('vas_fin_posting_rule_sets')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('entry_side', 10);
            $table->string('account_source', 40)->default('fixed');
            $table->foreignId('fixed_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->string('amount_source', 40)->default('line_amount');
            $table->string('description_template')->nullable();
            $table->boolean('is_balancing_line')->default(false);
            $table->json('conditions')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['posting_rule_set_id', 'line_no'], 'vas_fin_posting_rule_lines_rule_line_unique');
        });
    }

    protected function createFinanceAccountingEventsTable(): void
    {
        if (Schema::hasTable('vas_fin_accounting_events')) {
            return;
        }

        Schema::create('vas_fin_accounting_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('posting_rule_set_id')->nullable()->constrained('vas_fin_posting_rule_sets')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('event_status', 30)->default('draft');
            $table->string('idempotency_key', 120);
            $table->char('source_hash', 64)->nullable();
            $table->date('posting_date');
            $table->string('currency_code', 10)->default('VND');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('total_debit', 22, 4)->default(0);
            $table->decimal('total_credit', 22, 4)->default(0);
            $table->unsignedInteger('prepared_by')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversal_event_id')->nullable();
            $table->json('warnings')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('prepared_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'idempotency_key'], 'vas_fin_accounting_events_business_idempotency_unique');
            $table->index(['business_id', 'document_id', 'event_type'], 'vas_fin_accounting_events_document_event_index');
        });
    }

    protected function createFinanceAccountingEventLinesTable(): void
    {
        if (Schema::hasTable('vas_fin_accounting_event_lines')) {
            return;
        }

        Schema::create('vas_fin_accounting_event_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_event_id')->constrained('vas_fin_accounting_events')->cascadeOnDelete();
            $table->foreignId('document_line_id')->nullable()->constrained('vas_fin_document_lines')->nullOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->foreignId('account_id')->constrained('vas_accounts')->cascadeOnDelete();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->foreignId('tax_code_id')->nullable()->constrained('vas_tax_codes')->nullOnDelete();
            $table->date('posting_date');
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->string('description')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->unique(['accounting_event_id', 'line_no'], 'vas_fin_accounting_event_lines_event_line_unique');
            $table->index(['business_id', 'account_id', 'posting_date'], 'vas_fin_accounting_event_lines_account_date_index');
        });
    }

    protected function createFinanceJournalEntriesTable(): void
    {
        if (Schema::hasTable('vas_fin_journal_entries')) {
            return;
        }

        Schema::create('vas_fin_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('accounting_event_id')->constrained('vas_fin_accounting_events')->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained('vas_accounting_periods')->nullOnDelete();
            $table->string('journal_no', 120);
            $table->string('journal_type', 40)->default('general');
            $table->date('posting_date');
            $table->decimal('total_debit', 22, 4)->default(0);
            $table->decimal('total_credit', 22, 4)->default(0);
            $table->string('status', 30)->default('posted');
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'journal_no'], 'vas_fin_journal_entries_business_journal_no_unique');
            $table->index(['business_id', 'posting_date'], 'vas_fin_journal_entries_business_posting_date_index');
        });
    }

    protected function createFinanceJournalEntryLinesTable(): void
    {
        if (Schema::hasTable('vas_fin_journal_entry_lines')) {
            return;
        }

        Schema::create('vas_fin_journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('journal_entry_id')->constrained('vas_fin_journal_entries')->cascadeOnDelete();
            $table->foreignId('accounting_event_line_id')->nullable()->constrained('vas_fin_accounting_event_lines')->nullOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->foreignId('account_id')->constrained('vas_accounts')->cascadeOnDelete();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->foreignId('tax_code_id')->nullable()->constrained('vas_tax_codes')->nullOnDelete();
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->string('description')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->unique(['journal_entry_id', 'line_no'], 'vas_fin_journal_entry_lines_journal_line_unique');
            $table->index(['business_id', 'account_id'], 'vas_fin_journal_entry_lines_business_account_index');
        });
    }

    protected function createFinanceTraceLinksTable(): void
    {
        if (Schema::hasTable('vas_fin_trace_links')) {
            return;
        }

        Schema::create('vas_fin_trace_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('document_line_id')->nullable()->constrained('vas_fin_document_lines')->nullOnDelete();
            $table->foreignId('accounting_event_id')->nullable()->constrained('vas_fin_accounting_events')->nullOnDelete();
            $table->foreignId('accounting_event_line_id')->nullable()->constrained('vas_fin_accounting_event_lines')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('vas_fin_journal_entries')->nullOnDelete();
            $table->foreignId('journal_entry_line_id')->nullable()->constrained('vas_fin_journal_entry_lines')->nullOnDelete();
            $table->foreignId('upstream_document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->foreignId('downstream_document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->string('link_type', 40)->default('document_event');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['document_id'], 'vas_fin_trace_links_document_index');
            $table->index(['accounting_event_id'], 'vas_fin_trace_links_event_index');
            $table->index(['journal_entry_id'], 'vas_fin_trace_links_journal_index');
        });
    }

    protected function createFinanceAuditEventsTable(): void
    {
        if (Schema::hasTable('vas_fin_audit_events')) {
            return;
        }

        Schema::create('vas_fin_audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->foreignId('accounting_event_id')->nullable()->constrained('vas_fin_accounting_events')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('vas_fin_journal_entries')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('actor_type', 30)->default('user');
            $table->unsignedInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('request_id', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'event_type', 'acted_at'], 'vas_fin_audit_events_business_event_acted_index');
        });
    }

    protected function createFinanceDuplicateChecksTable(): void
    {
        if (Schema::hasTable('vas_fin_duplicate_checks')) {
            return;
        }

        Schema::create('vas_fin_duplicate_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->string('duplicate_type', 40);
            $table->string('fingerprint', 120);
            $table->string('status', 30)->default('clear');
            $table->json('matches')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'duplicate_type', 'fingerprint'], 'vas_fin_duplicate_checks_unique');
            $table->index(['business_id', 'status'], 'vas_fin_duplicate_checks_business_status_index');
        });
    }
}
