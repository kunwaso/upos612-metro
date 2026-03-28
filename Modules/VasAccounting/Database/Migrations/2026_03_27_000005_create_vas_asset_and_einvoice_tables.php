<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasAssetAndEinvoiceTables extends Migration
{
    public function up()
    {
        Schema::create('vas_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('name');
            $table->foreignId('asset_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->unsignedInteger('default_useful_life_months')->default(60);
            $table->string('depreciation_method', 50)->default('straight_line');
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });

        Schema::create('vas_fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('asset_category_id')->nullable()->constrained('vas_asset_categories')->nullOnDelete();
            $table->string('asset_code', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('acquisition_date');
            $table->date('capitalization_date');
            $table->unsignedInteger('purchase_transaction_id')->nullable();
            $table->unsignedInteger('vendor_contact_id')->nullable();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->decimal('original_cost', 22, 4)->default(0);
            $table->decimal('salvage_value', 22, 4)->default(0);
            $table->unsignedInteger('useful_life_months')->default(60);
            $table->decimal('monthly_depreciation', 22, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->foreignId('asset_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('vas_accounts')->nullOnDelete();
            $table->date('disposed_at')->nullable();
            $table->foreignId('disposal_voucher_id')->nullable()->constrained('vas_vouchers')->nullOnDelete();
            $table->unsignedInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('purchase_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->foreign('vendor_contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'asset_code']);
        });

        Schema::create('vas_asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('fixed_asset_id')->constrained('vas_fixed_assets')->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->constrained('vas_accounting_periods')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vas_vouchers')->nullOnDelete();
            $table->date('depreciation_date');
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique(['business_id', 'fixed_asset_id', 'accounting_period_id'], 'vas_asset_depreciations_unique');
        });

        Schema::create('vas_einvoice_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('voucher_id')->nullable()->constrained('vas_vouchers')->nullOnDelete();
            $table->unsignedInteger('transaction_id')->nullable();
            $table->string('provider', 50)->default('sandbox');
            $table->string('provider_document_id')->nullable();
            $table->string('document_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('replaced_by_id')->nullable();
            $table->json('source_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
        });

        Schema::create('vas_einvoice_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('einvoice_document_id')->constrained('vas_einvoice_documents')->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('status', 30)->default('queued');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vas_einvoice_logs');
        Schema::dropIfExists('vas_einvoice_documents');
        Schema::dropIfExists('vas_asset_depreciations');
        Schema::dropIfExists('vas_fixed_assets');
        Schema::dropIfExists('vas_asset_categories');
    }
}
