<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProjectxFabricSuppliersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_fabric_suppliers')) {
            return;
        }

        Schema::create('projectx_fabric_suppliers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('fabric_id');
            $table->foreign('fabric_id')
                ->references('id')
                ->on('projectx_fabrics')
                ->onDelete('cascade');

            $table->unsignedBigInteger('contact_id');
            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('cascade');

            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['fabric_id', 'contact_id']);
            $table->index('fabric_id');
            $table->index('contact_id');
        });

        $now = now();
        $legacySupplierRows = DB::table('projectx_fabrics as fabrics')
            ->join('contacts', function ($join) {
                $join->on('contacts.id', '=', 'fabrics.supplier_contact_id')
                    ->on('contacts.business_id', '=', 'fabrics.business_id');
            })
            ->whereNotNull('fabrics.supplier_contact_id')
            ->whereIn('contacts.type', ['supplier', 'both'])
            ->select([
                'fabrics.id as fabric_id',
                'fabrics.supplier_contact_id as contact_id',
            ])
            ->orderBy('fabrics.id')
            ->get();

        foreach ($legacySupplierRows as $legacySupplierRow) {
            DB::table('projectx_fabric_suppliers')->updateOrInsert(
                [
                    'fabric_id' => (int) $legacySupplierRow->fabric_id,
                    'contact_id' => (int) $legacySupplierRow->contact_id,
                ],
                [
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down()
    {
        Schema::dropIfExists('projectx_fabric_suppliers');
    }
}
