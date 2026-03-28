<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasLedgerTables extends Migration
{
    public function up()
    {
        Schema::create('vas_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_period_id')->constrained('vas_accounting_periods')->cascadeOnDelete();
            $table->string('voucher_no', 80);
            $table->string('voucher_type', 50);
            $table->string('sequence_key', 60)->default('general_journal');
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->unsignedInteger('transaction_id')->nullable();
            $table->unsignedInteger('transaction_payment_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->date('posting_date');
            $table->date('document_date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('currency_code', 10)->default('VND');
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('total_debit', 22, 4)->default(0);
            $table->decimal('total_credit', 22, 4)->default(0);
            $table->boolean('is_system_generated')->default(true);
            $table->boolean('is_reversal')->default(false);
            $table->unsignedInteger('version_no')->default(1);
            $table->foreignId('reversed_voucher_id')->nullable()->constrained('vas_vouchers')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('transaction_payment_id')->references('id')->on('transaction_payments')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'voucher_no']);
            $table->index(['business_id', 'source_type', 'source_id']);
        });

        Schema::create('vas_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('voucher_id')->constrained('vas_vouchers')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->foreignId('account_id')->constrained('vas_accounts')->cascadeOnDelete();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->foreignId('tax_code_id')->nullable()->constrained('vas_tax_codes')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->index(['voucher_id', 'line_no']);
        });

        Schema::create('vas_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_period_id')->constrained('vas_accounting_periods')->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained('vas_vouchers')->cascadeOnDelete();
            $table->foreignId('voucher_line_id')->constrained('vas_voucher_lines')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('vas_accounts')->cascadeOnDelete();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->foreignId('tax_code_id')->nullable()->constrained('vas_tax_codes')->nullOnDelete();
            $table->date('posting_date');
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->index(['business_id', 'account_id', 'posting_date']);
        });

        Schema::create('vas_ledger_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_period_id')->constrained('vas_accounting_periods')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('vas_accounts')->cascadeOnDelete();
            $table->decimal('opening_debit', 22, 4)->default(0);
            $table->decimal('opening_credit', 22, 4)->default(0);
            $table->decimal('period_debit', 22, 4)->default(0);
            $table->decimal('period_credit', 22, 4)->default(0);
            $table->decimal('closing_debit', 22, 4)->default(0);
            $table->decimal('closing_credit', 22, 4)->default(0);
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'accounting_period_id', 'account_id'], 'vas_ledger_balances_unique');
        });

        Schema::create('vas_posting_failures', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('source_type', 60);
            $table->unsignedBigInteger('source_id');
            $table->string('listener', 191)->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('error_trace')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'source_type', 'source_id']);
        });

        Schema::create('vas_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_period_id')->nullable()->constrained('vas_accounting_periods')->nullOnDelete();
            $table->string('report_key', 80);
            $table->unsignedInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->json('filters')->nullable();
            $table->longText('payload')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vas_report_snapshots');
        Schema::dropIfExists('vas_posting_failures');
        Schema::dropIfExists('vas_ledger_balances');
        Schema::dropIfExists('vas_journal_entries');
        Schema::dropIfExists('vas_voucher_lines');
        Schema::dropIfExists('vas_vouchers');
    }
}
