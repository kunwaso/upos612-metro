<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVasRolloutFoundationTables extends Migration
{
    public function up()
    {
        $this->alterDocumentApprovalsTable();
        $this->createApprovalRulesTable();
        $this->createApprovalRuleStepsTable();
        $this->createExchangeRatesTable();
        $this->createPayrollProfilesTable();
        $this->createPayrollPeriodsTable();
        $this->createPayrollRunsTable();
        $this->createPayrollRunLinesTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_payroll_run_lines');
        Schema::dropIfExists('vas_payroll_runs');
        Schema::dropIfExists('vas_payroll_periods');
        Schema::dropIfExists('vas_payroll_profiles');
        Schema::dropIfExists('vas_exchange_rates');
        Schema::dropIfExists('vas_approval_rule_steps');
        Schema::dropIfExists('vas_approval_rules');

        if (Schema::hasTable('vas_document_approvals')) {
            Schema::table('vas_document_approvals', function (Blueprint $table) {
                if (Schema::hasColumn('vas_document_approvals', 'approval_rule_step_id')) {
                    $table->dropColumn('approval_rule_step_id');
                }
                if (Schema::hasColumn('vas_document_approvals', 'approval_rule_id')) {
                    $table->dropColumn('approval_rule_id');
                }
            });
        }
    }

    protected function alterDocumentApprovalsTable(): void
    {
        if (! Schema::hasTable('vas_document_approvals')) {
            return;
        }

        Schema::table('vas_document_approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('vas_document_approvals', 'approval_rule_id')) {
                $table->unsignedBigInteger('approval_rule_id')->nullable()->after('entity_id');
            }

            if (! Schema::hasColumn('vas_document_approvals', 'approval_rule_step_id')) {
                $table->unsignedBigInteger('approval_rule_step_id')->nullable()->after('approval_rule_id');
            }
        });
    }

    protected function createApprovalRulesTable(): void
    {
        if (Schema::hasTable('vas_approval_rules')) {
            return;
        }

        Schema::create('vas_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('rule_code', 80);
            $table->string('rule_name');
            $table->string('document_family', 40);
            $table->string('entity_type', 120)->default('voucher');
            $table->string('source_type', 60)->nullable();
            $table->string('module_area', 60)->nullable();
            $table->string('document_type', 60)->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->decimal('min_amount', 22, 4)->nullable();
            $table->decimal('max_amount', 22, 4)->nullable();
            $table->decimal('auto_approve_below', 22, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->unique(['business_id', 'rule_code'], 'vas_approval_rules_business_rule_code_unique');
            $table->index(['business_id', 'document_family', 'is_active'], 'vas_approval_rules_business_family_active_index');
        });
    }

    protected function createApprovalRuleStepsTable(): void
    {
        if (Schema::hasTable('vas_approval_rule_steps')) {
            return;
        }

        Schema::create('vas_approval_rule_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_rule_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('step_no')->default(1);
            $table->unsignedInteger('approver_user_id')->nullable();
            $table->string('approver_role', 60)->nullable();
            $table->string('permission_code', 120)->nullable();
            $table->string('action_label', 60)->default('approve');
            $table->boolean('is_required')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('approval_rule_id')->references('id')->on('vas_approval_rules')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('approver_user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['approval_rule_id', 'step_no'], 'vas_approval_rule_steps_rule_step_unique');
            $table->index(['business_id', 'approver_user_id'], 'vas_approval_rule_steps_business_approver_index');
        });
    }

    protected function createExchangeRatesTable(): void
    {
        if (Schema::hasTable('vas_exchange_rates')) {
            return;
        }

        Schema::create('vas_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->date('rate_date');
            $table->string('from_currency', 10);
            $table->string('to_currency', 10)->default('VND');
            $table->decimal('rate', 20, 8);
            $table->decimal('inverse_rate', 20, 8)->nullable();
            $table->string('source', 60)->default('manual');
            $table->boolean('is_manual')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'rate_date', 'from_currency', 'to_currency'], 'vas_exchange_rates_business_date_pair_unique');
            $table->index(['business_id', 'from_currency', 'to_currency'], 'vas_exchange_rates_business_pair_index');
        });
    }

    protected function createPayrollProfilesTable(): void
    {
        if (Schema::hasTable('vas_payroll_profiles')) {
            return;
        }

        Schema::create('vas_payroll_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('profile_code', 80);
            $table->string('profile_name');
            $table->string('pay_frequency', 30)->default('monthly');
            $table->string('currency_code', 10)->default('VND');
            $table->foreignId('expense_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('payable_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->json('earning_components')->nullable();
            $table->json('deduction_components')->nullable();
            $table->json('statutory_components')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->unique(['business_id', 'profile_code'], 'vas_payroll_profiles_business_code_unique');
            $table->index(['business_id', 'employee_id'], 'vas_payroll_profiles_business_employee_index');
        });
    }

    protected function createPayrollPeriodsTable(): void
    {
        if (Schema::hasTable('vas_payroll_periods')) {
            return;
        }

        Schema::create('vas_payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('period_code', 80);
            $table->string('period_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('payment_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('source_mode', 30)->default('native');
            $table->unsignedBigInteger('bridge_batch_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('closed_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'period_code'], 'vas_payroll_periods_business_code_unique');
            $table->index(['business_id', 'status'], 'vas_payroll_periods_business_status_index');
        });
    }

    protected function createPayrollRunsTable(): void
    {
        if (Schema::hasTable('vas_payroll_runs')) {
            return;
        }

        Schema::create('vas_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('reference_no', 120)->nullable();
            $table->string('run_type', 30)->default('monthly');
            $table->string('status', 30)->default('draft');
            $table->decimal('gross_total', 22, 4)->default(0);
            $table->decimal('employee_deduction_total', 22, 4)->default(0);
            $table->decimal('employer_contribution_total', 22, 4)->default(0);
            $table->decimal('net_total', 22, 4)->default(0);
            $table->unsignedBigInteger('accrual_voucher_id')->nullable();
            $table->unsignedBigInteger('payment_voucher_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('payroll_period_id')->references('id')->on('vas_payroll_periods')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('accrual_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('payment_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'status'], 'vas_payroll_runs_business_status_index');
        });
    }

    protected function createPayrollRunLinesTable(): void
    {
        if (Schema::hasTable('vas_payroll_run_lines')) {
            return;
        }

        Schema::create('vas_payroll_run_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('payroll_profile_id')->nullable();
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('employee_name')->nullable();
            $table->string('currency_code', 10)->default('VND');
            $table->decimal('gross_amount', 22, 4)->default(0);
            $table->decimal('employee_deductions', 22, 4)->default(0);
            $table->decimal('employer_contributions', 22, 4)->default(0);
            $table->decimal('net_amount', 22, 4)->default(0);
            $table->json('earnings')->nullable();
            $table->json('deductions')->nullable();
            $table->json('statutory_breakdown')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('payroll_run_id')->references('id')->on('vas_payroll_runs')->onDelete('cascade');
            $table->foreign('payroll_profile_id')->references('id')->on('vas_payroll_profiles')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['payroll_run_id', 'line_no'], 'vas_payroll_run_lines_run_line_unique');
            $table->index(['business_id', 'employee_id'], 'vas_payroll_run_lines_business_employee_index');
        });
    }
}
