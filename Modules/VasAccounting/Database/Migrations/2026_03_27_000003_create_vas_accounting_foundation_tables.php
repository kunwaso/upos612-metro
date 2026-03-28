<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVasAccountingFoundationTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('vas_business_settings')) {
            // Recover from a previously interrupted run where the table exists
            // but this migration was never recorded.
            if (DB::table('vas_business_settings')->count() > 0) {
                throw new RuntimeException(
                    'Cannot auto-reconcile vas_business_settings because it already contains data.'
                );
            }

            Schema::drop('vas_business_settings');
        }

        Schema::create('vas_business_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('book_currency', 10)->default('VND');
            $table->string('inventory_method', 50)->default('weighted_average');
            $table->boolean('is_enabled')->default(true);
            $table->json('posting_map')->nullable();
            $table->json('compliance_settings')->nullable();
            $table->json('inventory_settings')->nullable();
            $table->json('depreciation_settings')->nullable();
            $table->json('tax_settings')->nullable();
            $table->json('einvoice_settings')->nullable();
            $table->json('report_preferences')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique('business_id');
        });

        Schema::create('vas_accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('open');
            $table->boolean('is_adjustment_period')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('closed_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'start_date', 'end_date']);
        });

        Schema::create('vas_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('parent_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->string('account_code', 30);
            $table->string('account_name');
            $table->string('account_type', 50);
            $table->string('account_category', 100)->nullable();
            $table->string('normal_balance', 10)->default('debit');
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('allows_manual_entries')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'account_code']);
            $table->index(['business_id', 'account_type']);
        });

        Schema::create('vas_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('code', 50);
            $table->string('name');
            $table->string('direction', 20)->default('both');
            $table->decimal('rate', 8, 4)->default(0);
            $table->foreignId('payable_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('receivable_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'code']);
        });

        Schema::create('vas_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('sequence_key', 60);
            $table->string('prefix', 20);
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->string('reset_frequency', 20)->default('yearly');
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'sequence_key']);
        });

        Schema::create('vas_opening_balance_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('accounting_period_id')->nullable()->constrained('vas_accounting_periods')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('imported_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('imported_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vas_opening_balance_batches');
        Schema::dropIfExists('vas_document_sequences');
        Schema::dropIfExists('vas_tax_codes');
        Schema::dropIfExists('vas_accounts');
        Schema::dropIfExists('vas_accounting_periods');
        Schema::dropIfExists('vas_business_settings');
    }
}
