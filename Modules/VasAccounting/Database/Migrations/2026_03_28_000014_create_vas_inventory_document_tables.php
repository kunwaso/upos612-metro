<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasInventoryDocumentTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('vas_inventory_documents')) {
            Schema::create('vas_inventory_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->unsignedBigInteger('accounting_period_id')->nullable();
                $table->string('document_no', 60);
                $table->string('document_type', 40);
                $table->string('sequence_key', 40);
                $table->unsignedInteger('business_location_id')->nullable();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('destination_warehouse_id')->nullable();
                $table->unsignedBigInteger('offset_account_id')->nullable();
                $table->date('posting_date');
                $table->date('document_date');
                $table->string('status', 30)->default('draft');
                $table->string('reference', 120)->nullable();
                $table->string('external_reference', 120)->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('posted_voucher_id')->nullable();
                $table->unsignedBigInteger('reversal_voucher_id')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->unsignedInteger('posted_by')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->unsignedInteger('reversed_by')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('accounting_period_id')->references('id')->on('vas_accounting_periods')->onDelete('set null');
                $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
                $table->foreign('warehouse_id')->references('id')->on('vas_warehouses')->onDelete('set null');
                $table->foreign('destination_warehouse_id')->references('id')->on('vas_warehouses')->onDelete('set null');
                $table->foreign('offset_account_id')->references('id')->on('vas_accounts')->onDelete('set null');
                $table->foreign('posted_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
                $table->foreign('reversal_voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
                $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['business_id', 'document_no'], 'vas_inventory_documents_no_unique');
                $table->index(['business_id', 'document_type', 'status'], 'vas_inventory_documents_type_status_idx');
            });
        }

        if (! Schema::hasTable('vas_inventory_document_lines')) {
            Schema::create('vas_inventory_document_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->unsignedBigInteger('inventory_document_id');
                $table->unsignedInteger('line_no');
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('variation_id')->nullable();
                $table->decimal('quantity', 22, 4)->default(0);
                $table->decimal('unit_cost', 22, 4)->default(0);
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('direction', 20)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('inventory_document_id')->references('id')->on('vas_inventory_documents')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->index(['business_id', 'product_id'], 'vas_inventory_document_lines_product_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('vas_inventory_document_lines');
        Schema::dropIfExists('vas_inventory_documents');
    }
}
