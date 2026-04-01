<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasFinanceInventoryLedgerTables extends Migration
{
    public function up()
    {
        $this->createFinanceInventoryMovementsTable();
        $this->createFinanceInventoryCostLayersTable();
        $this->createFinanceInventoryCostSettlementsTable();
    }

    public function down()
    {
        Schema::dropIfExists('vas_fin_inventory_cost_settlements');
        Schema::dropIfExists('vas_fin_inventory_cost_layers');
        Schema::dropIfExists('vas_fin_inventory_movements');
    }

    protected function createFinanceInventoryMovementsTable(): void
    {
        if (Schema::hasTable('vas_fin_inventory_movements')) {
            return;
        }

        Schema::create('vas_fin_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('document_id')->constrained('vas_fin_documents')->cascadeOnDelete();
            $table->foreignId('document_line_id')->nullable()->constrained('vas_fin_document_lines')->nullOnDelete();
            $table->foreignId('accounting_event_id')->nullable()->constrained('vas_fin_accounting_events')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('movement_type', 40);
            $table->string('direction', 10);
            $table->string('status', 20)->default('active');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->string('currency_code', 10)->default('VND');
            $table->date('movement_date');
            $table->foreignId('reversal_movement_id')->nullable()->constrained('vas_fin_inventory_movements')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'product_id', 'business_location_id', 'movement_date'], 'vas_fin_inventory_movements_product_loc_date_index');
            $table->index(['document_id', 'status'], 'vas_fin_inventory_movements_document_status_index');
        });
    }

    protected function createFinanceInventoryCostLayersTable(): void
    {
        if (Schema::hasTable('vas_fin_inventory_cost_layers')) {
            return;
        }

        Schema::create('vas_fin_inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('business_location_id')->nullable();
            $table->string('currency_code', 10)->default('VND');
            $table->string('costing_method', 30)->default('weighted_average');
            $table->string('layer_type', 30)->default('weighted_average_pool');
            $table->foreignId('source_document_id')->nullable()->constrained('vas_fin_documents')->nullOnDelete();
            $table->foreignId('source_document_line_id')->nullable()->constrained('vas_fin_document_lines')->nullOnDelete();
            $table->foreignId('receipt_movement_id')->nullable()->constrained('vas_fin_inventory_movements')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->decimal('quantity_in', 22, 4)->default(0);
            $table->decimal('quantity_out', 22, 4)->default(0);
            $table->decimal('quantity_on_hand', 22, 4)->default(0);
            $table->decimal('total_value_in', 22, 4)->default(0);
            $table->decimal('total_value_out', 22, 4)->default(0);
            $table->decimal('total_value_on_hand', 22, 4)->default(0);
            $table->decimal('average_unit_cost', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->index(['business_id', 'product_id', 'business_location_id', 'costing_method'], 'vas_fin_inventory_cost_layers_lookup_index');
        });
    }

    protected function createFinanceInventoryCostSettlementsTable(): void
    {
        if (Schema::hasTable('vas_fin_inventory_cost_settlements')) {
            return;
        }

        Schema::create('vas_fin_inventory_cost_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->foreignId('issue_movement_id')->constrained('vas_fin_inventory_movements')->cascadeOnDelete();
            $table->foreignId('cost_layer_id')->constrained('vas_fin_inventory_cost_layers')->cascadeOnDelete();
            $table->decimal('settled_quantity', 22, 4)->default(0);
            $table->decimal('settled_value', 22, 4)->default(0);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['issue_movement_id', 'cost_layer_id'], 'vas_fin_inventory_cost_settlements_issue_layer_index');
        });
    }
}
