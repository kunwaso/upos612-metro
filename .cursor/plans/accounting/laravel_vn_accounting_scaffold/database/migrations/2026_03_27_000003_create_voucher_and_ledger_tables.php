<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('accounting_periods')->nullOnDelete();
            $table->string('voucher_type');
            $table->string('voucher_no');
            $table->date('voucher_date');
            $table->date('posting_date');
            $table->date('document_date')->nullable();
            $table->string('document_no')->nullable();
            $table->text('description')->nullable();
            $table->string('currency_code', 3)->default('VND');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('status')->default('draft');
            $table->string('source_module')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('workflow_status')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'voucher_type', 'voucher_no']);
            $table->index(['source_module', 'source_id']);
        });

        Schema::create('voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_no');
            $table->text('description')->nullable();
            $table->foreignId('debit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('credit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->decimal('amount_fc', 18, 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('fixed_assets')->nullOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('entry_no');
            $table->date('entry_date');
            $table->date('posting_date');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('debit_amount', 18, 2)->default(0);
            $table->decimal('credit_amount', 18, 2)->default(0);
            $table->decimal('debit_amount_fc', 18, 2)->nullable();
            $table->decimal('credit_amount_fc', 18, 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('fixed_assets')->nullOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'posting_date']);
        });

        Schema::create('ledger_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('fixed_assets')->nullOnDelete();
            $table->decimal('opening_debit', 18, 2)->default(0);
            $table->decimal('opening_credit', 18, 2)->default(0);
            $table->decimal('movement_debit', 18, 2)->default(0);
            $table->decimal('movement_credit', 18, 2)->default(0);
            $table->decimal('closing_debit', 18, 2)->default(0);
            $table->decimal('closing_credit', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['organization_id', 'period_id', 'account_id'], 'idx_ledger_org_period_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_balances');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('voucher_lines');
        Schema::dropIfExists('vouchers');
    }
};
