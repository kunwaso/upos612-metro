<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasFinanceOpenItemTables extends Migration
{
    public function up()
    {
        $this->createFinanceOpenItemsTable();
        $this->createFinanceOpenItemAllocationsTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_fin_open_item_allocations');
        Schema::dropIfExists('vas_fin_open_items');
    }

    protected function createFinanceOpenItemsTable(): void
    {
        if (Schema::hasTable('vas_fin_open_items')) {
            return;
        }

        Schema::create('vas_fin_open_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('accounting_event_id')->nullable()->constrained('vas_fin_accounting_events')->nullOnDelete();
            $table->string('ledger_type', 30);
            $table->string('document_role', 30);
            $table->string('counterparty_type', 30)->nullable();
            $table->unsignedInteger('counterparty_id')->nullable();
            $table->string('currency_code', 10)->default('VND');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->date('document_date');
            $table->date('posting_date');
            $table->date('due_date')->nullable();
            $table->string('reference_no', 120)->nullable();
            $table->decimal('original_amount', 22, 4)->default(0);
            $table->decimal('open_amount', 22, 4)->default(0);
            $table->decimal('settled_amount', 22, 4)->default(0);
            $table->string('status', 30)->default('open');
            $table->foreignId('reversal_event_id')->nullable()->constrained('vas_fin_accounting_events')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('counterparty_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['document_id', 'ledger_type', 'document_role'], 'vas_fin_open_items_document_ledger_role_unique');
            $table->index(['business_id', 'ledger_type', 'status', 'due_date'], 'vas_fin_open_items_ledger_status_due_index');
            $table->index(['business_id', 'counterparty_id', 'status'], 'vas_fin_open_items_counterparty_status_index');
        });
    }

    protected function createFinanceOpenItemAllocationsTable(): void
    {
        if (Schema::hasTable('vas_fin_open_item_allocations')) {
            return;
        }

        Schema::create('vas_fin_open_item_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('source_open_item_id')->constrained('vas_fin_open_items')->cascadeOnDelete();
            $table->foreignId('target_open_item_id')->constrained('vas_fin_open_items')->cascadeOnDelete();
            $table->foreignId('accounting_event_id')->nullable()->constrained('vas_fin_accounting_events')->nullOnDelete();
            $table->foreignId('reverses_allocation_id')->nullable()->constrained('vas_fin_open_item_allocations')->nullOnDelete();
            $table->string('allocation_type', 30)->default('settlement');
            $table->string('status', 30)->default('active');
            $table->date('allocation_date');
            $table->string('currency_code', 10)->default('VND');
            $table->decimal('amount', 22, 4)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedInteger('acted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('acted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['business_id', 'status', 'allocation_date'], 'vas_fin_open_item_allocations_status_date_index');
            $table->index(['source_open_item_id', 'status'], 'vas_fin_open_item_allocations_source_status_index');
            $table->index(['target_open_item_id', 'status'], 'vas_fin_open_item_allocations_target_status_index');
        });
    }
}
