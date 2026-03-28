<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandVasEnterpriseCoreTables extends Migration
{
    public function up()
    {
        $this->alterBusinessSettingsTable();
        $this->alterVoucherTable();
        $this->alterVoucherLineTable();
        $this->alterJournalEntryTable();

        $this->createDepartmentsTable();
        $this->createCostCentersTable();
        $this->createProjectsTable();
        $this->createCashbooksTable();
        $this->createBankAccountsTable();
        $this->createWarehousesTable();
        $this->createToolsTable();
        $this->createContractsTable();
        $this->createLoansTable();
        $this->createBudgetsTable();
        $this->createBudgetLinesTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_budget_lines');
        Schema::dropIfExists('vas_budgets');
        Schema::dropIfExists('vas_loans');
        Schema::dropIfExists('vas_contracts');
        Schema::dropIfExists('vas_tools');
        Schema::dropIfExists('vas_warehouses');
        Schema::dropIfExists('vas_bank_accounts');
        Schema::dropIfExists('vas_cashbooks');
        Schema::dropIfExists('vas_projects');
        Schema::dropIfExists('vas_cost_centers');
        Schema::dropIfExists('vas_departments');
    }

    protected function alterBusinessSettingsTable(): void
    {
        Schema::table('vas_business_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_business_settings', 'feature_flags')) {
                $table->json('feature_flags')->nullable()->after('report_preferences');
            }
            if (! Schema::hasColumn('vas_business_settings', 'approval_settings')) {
                $table->json('approval_settings')->nullable()->after('feature_flags');
            }
            if (! Schema::hasColumn('vas_business_settings', 'branch_settings')) {
                $table->json('branch_settings')->nullable()->after('approval_settings');
            }
            if (! Schema::hasColumn('vas_business_settings', 'integration_settings')) {
                $table->json('integration_settings')->nullable()->after('branch_settings');
            }
            if (! Schema::hasColumn('vas_business_settings', 'budget_settings')) {
                $table->json('budget_settings')->nullable()->after('integration_settings');
            }
        });
    }

    protected function alterVoucherTable(): void
    {
        Schema::table('vas_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_vouchers', 'module_area')) {
                $table->string('module_area', 60)->nullable()->after('voucher_type');
            }
            if (! Schema::hasColumn('vas_vouchers', 'document_type')) {
                $table->string('document_type', 60)->nullable()->after('module_area');
            }
            if (! Schema::hasColumn('vas_vouchers', 'external_reference')) {
                $table->string('external_reference', 120)->nullable()->after('reference');
            }
            if (! Schema::hasColumn('vas_vouchers', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('posted_at');
            }
            if (! Schema::hasColumn('vas_vouchers', 'submitted_by')) {
                $table->unsignedInteger('submitted_by')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('vas_vouchers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_by');
            }
            if (! Schema::hasColumn('vas_vouchers', 'approved_by')) {
                $table->unsignedInteger('approved_by')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('vas_vouchers', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('vas_vouchers', 'cancelled_by')) {
                $table->unsignedInteger('cancelled_by')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('vas_vouchers', 'is_historical_import')) {
                $table->boolean('is_historical_import')->default(false)->after('is_system_generated');
            }
        });
    }

    protected function alterVoucherLineTable(): void
    {
        Schema::table('vas_voucher_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_voucher_lines', 'employee_id')) {
                $table->unsignedInteger('employee_id')->nullable()->after('contact_id');
            }
            if (! Schema::hasColumn('vas_voucher_lines', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('vas_voucher_lines', 'cost_center_id')) {
                $table->unsignedBigInteger('cost_center_id')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('vas_voucher_lines', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('cost_center_id');
            }
            if (! Schema::hasColumn('vas_voucher_lines', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('vas_voucher_lines', 'asset_id')) {
                $table->unsignedBigInteger('asset_id')->nullable()->after('warehouse_id');
            }
            if (! Schema::hasColumn('vas_voucher_lines', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('asset_id');
            }
            if (! Schema::hasColumn('vas_voucher_lines', 'budget_id')) {
                $table->unsignedBigInteger('budget_id')->nullable()->after('contract_id');
            }
        });
    }

    protected function alterJournalEntryTable(): void
    {
        Schema::table('vas_journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_journal_entries', 'employee_id')) {
                $table->unsignedInteger('employee_id')->nullable()->after('contact_id');
            }
            if (! Schema::hasColumn('vas_journal_entries', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('vas_journal_entries', 'cost_center_id')) {
                $table->unsignedBigInteger('cost_center_id')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('vas_journal_entries', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('cost_center_id');
            }
            if (! Schema::hasColumn('vas_journal_entries', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('vas_journal_entries', 'asset_id')) {
                $table->unsignedBigInteger('asset_id')->nullable()->after('warehouse_id');
            }
            if (! Schema::hasColumn('vas_journal_entries', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('asset_id');
            }
            if (! Schema::hasColumn('vas_journal_entries', 'budget_id')) {
                $table->unsignedBigInteger('budget_id')->nullable()->after('contract_id');
            }
        });
    }

    protected function createDepartmentsTable(): void
    {
        if (Schema::hasTable('vas_departments')) {
            return;
        }

        Schema::create('vas_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('code', 60);
            $table->string('name');
            $table->unsignedInteger('business_location_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->unique(['business_id', 'code']);
        });
    }

    protected function createCostCentersTable(): void
    {
        if (Schema::hasTable('vas_cost_centers')) {
            return;
        }

        Schema::create('vas_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('code', 60);
            $table->string('name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'code']);
        });
    }

    protected function createProjectsTable(): void
    {
        if (Schema::hasTable('vas_projects')) {
            return;
        }

        Schema::create('vas_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('project_code', 80);
            $table->string('name');
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->decimal('budget_amount', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->unique(['business_id', 'project_code']);
        });
    }

    protected function createCashbooksTable(): void
    {
        if (Schema::hasTable('vas_cashbooks')) {
            return;
        }

        Schema::create('vas_cashbooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('code', 60);
            $table->string('name');
            $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('cash_account_id')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('cash_account_id')->references('id')->on('vas_accounts')->onDelete('set null');
            $table->unique(['business_id', 'code']);
        });
    }

    protected function createBankAccountsTable(): void
    {
        if (Schema::hasTable('vas_bank_accounts')) {
            return;
        }

        Schema::create('vas_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('account_code', 80);
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number', 120);
            $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('ledger_account_id')->nullable();
            $table->string('currency_code', 10)->default('VND');
            $table->string('status', 30)->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('ledger_account_id')->references('id')->on('vas_accounts')->onDelete('set null');
            $table->unique(['business_id', 'account_code']);
        });
    }

    protected function createWarehousesTable(): void
    {
        if (Schema::hasTable('vas_warehouses')) {
            return;
        }

        Schema::create('vas_warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('code', 80);
            $table->string('name');
            $table->string('status', 30)->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->unique(['business_id', 'code']);
        });
    }

    protected function createToolsTable(): void
    {
        if (Schema::hasTable('vas_tools')) {
            return;
        }

        Schema::create('vas_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('tool_code', 80);
            $table->string('name');
            $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->decimal('original_cost', 22, 4)->default(0);
            $table->decimal('remaining_value', 22, 4)->default(0);
            $table->unsignedInteger('amortization_months')->default(12);
            $table->string('status', 30)->default('draft');
            $table->date('start_amortization_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->unique(['business_id', 'tool_code']);
        });
    }

    protected function createContractsTable(): void
    {
        if (Schema::hasTable('vas_contracts')) {
            return;
        }

        Schema::create('vas_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('contract_no', 80);
            $table->string('name');
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->date('signed_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('contract_value', 22, 4)->default(0);
            $table->decimal('advance_amount', 22, 4)->default(0);
            $table->decimal('retention_amount', 22, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->unique(['business_id', 'contract_no']);
        });
    }

    protected function createLoansTable(): void
    {
        if (Schema::hasTable('vas_loans')) {
            return;
        }

        Schema::create('vas_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('loan_no', 80);
            $table->string('lender_name');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->decimal('principal_amount', 22, 4)->default(0);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->date('disbursement_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'loan_no']);
        });
    }

    protected function createBudgetsTable(): void
    {
        if (Schema::hasTable('vas_budgets')) {
            return;
        }

        Schema::create('vas_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('budget_code', 80);
            $table->string('name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('draft');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'budget_code']);
        });
    }

    protected function createBudgetLinesTable(): void
    {
        if (Schema::hasTable('vas_budget_lines')) {
            return;
        }

        Schema::create('vas_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->decimal('budget_amount', 22, 4)->default(0);
            $table->decimal('committed_amount', 22, 4)->default(0);
            $table->decimal('actual_amount', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('budget_id')->references('id')->on('vas_budgets')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('vas_accounts')->onDelete('set null');
        });
    }
}
