<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasContractAndLoanScheduleTables extends Migration
{
    public function up()
    {
        $this->createContractMilestonesTable();
        $this->createLoanRepaymentSchedulesTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_loan_repayment_schedules');
        Schema::dropIfExists('vas_contract_milestones');
    }

    protected function createContractMilestonesTable(): void
    {
        if (Schema::hasTable('vas_contract_milestones')) {
            return;
        }

        Schema::create('vas_contract_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('contract_id');
            $table->string('milestone_no', 80);
            $table->string('name');
            $table->date('milestone_date')->nullable();
            $table->date('billing_date')->nullable();
            $table->decimal('revenue_amount', 22, 4)->default(0);
            $table->decimal('advance_amount', 22, 4)->default(0);
            $table->decimal('retention_amount', 22, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('posted_voucher_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('vas_contracts')->onDelete('cascade');
            $table->foreign('posted_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->unique(['business_id', 'contract_id', 'milestone_no'], 'vas_contract_milestones_unique');
        });
    }

    protected function createLoanRepaymentSchedulesTable(): void
    {
        if (Schema::hasTable('vas_loan_repayment_schedules')) {
            return;
        }

        Schema::create('vas_loan_repayment_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('loan_id');
            $table->date('due_date');
            $table->decimal('principal_due', 22, 4)->default(0);
            $table->decimal('interest_due', 22, 4)->default(0);
            $table->string('status', 30)->default('planned');
            $table->unsignedBigInteger('settled_voucher_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('loan_id')->references('id')->on('vas_loans')->onDelete('cascade');
            $table->foreign('settled_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->unique(['business_id', 'loan_id', 'due_date'], 'vas_loan_repayment_schedules_unique');
        });
    }
}
