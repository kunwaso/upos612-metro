<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('txn_type');
            $table->string('txn_no');
            $table->date('txn_date');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('inventory_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('lot_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('opening_qty', 18, 4)->default(0);
            $table->decimal('opening_amount', 18, 2)->default(0);
            $table->decimal('in_qty', 18, 4)->default(0);
            $table->decimal('in_amount', 18, 2)->default(0);
            $table->decimal('out_qty', 18, 4)->default(0);
            $table->decimal('out_amount', 18, 2)->default(0);
            $table->decimal('closing_qty', 18, 4)->default(0);
            $table->decimal('closing_amount', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'period_id', 'warehouse_id', 'item_id'], 'uq_inventory_balance_key');
        });

        Schema::create('asset_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->cascadeOnDelete();
            $table->date('run_date');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_depreciation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('asset_depreciation_runs')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->decimal('depreciation_amount', 18, 2)->default(0);
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('version')->nullable();
            $table->json('config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->json('parameters_json')->nullable();
            $table->string('output_path')->nullable();
            $table->string('status')->default('queued');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_definitions');
        Schema::dropIfExists('asset_depreciation_lines');
        Schema::dropIfExists('asset_depreciation_runs');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_transaction_lines');
        Schema::dropIfExists('inventory_transactions');
    }
};
