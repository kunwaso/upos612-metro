<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name_vi');
            $table->string('name_en')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('account_type');
            $table->string('normal_balance', 6);
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_postable')->default(true);
            $table->boolean('requires_customer')->default(false);
            $table->boolean('requires_vendor')->default(false);
            $table->boolean('requires_employee')->default(false);
            $table->boolean('requires_department')->default(false);
            $table->boolean('requires_cost_center')->default(false);
            $table->boolean('requires_project')->default(false);
            $table->boolean('requires_item')->default(false);
            $table->boolean('requires_asset')->default(false);
            $table->string('fs_mapping_code')->nullable();
            $table->string('status')->default('active');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('tax_code')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('tax_code')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('tax_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('currency_code', 3)->default('VND');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('bank_name');
            $table->string('bank_branch')->nullable();
            $table->string('account_no');
            $table->string('account_name');
            $table->string('currency_code', 3)->default('VND');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('item_type')->default('inventory');
            $table->string('unit_of_measure')->nullable();
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->string('asset_category')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('in_use_date')->nullable();
            $table->decimal('original_cost', 18, 2)->default(0);
            $table->unsignedInteger('useful_life_months')->default(0);
            $table->string('depreciation_method')->default('straight_line');
            $table->decimal('accumulated_depreciation', 18, 2)->default(0);
            $table->decimal('residual_value', 18, 2)->default(0);
            $table->foreignId('asset_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('depreciation_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'asset_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('items');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('cash_accounts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('accounts');
    }
};
