<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProjectxFabricComponentsTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('projectx_fabric_component_catalog')) {
        Schema::create('projectx_fabric_component_catalog', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->string('label', 100);
            $table->json('aliases')->nullable();
            $table->string('default_unit', 20)->default('percent');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('business_id');
            $table->index('sort_order');
            $table->index('label');

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
        }

        if (!Schema::hasTable('projectx_fabric_composition_items')) {
        Schema::create('projectx_fabric_composition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fabric_id');
            $table->unsignedBigInteger('fabric_component_catalog_id')->nullable();
            $table->string('label_override')->nullable();
            $table->decimal('percent', 5, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('fabric_id')
                ->references('id')
                ->on('projectx_fabrics')
                ->onDelete('cascade');

            $table->foreign('fabric_component_catalog_id', 'px_fab_comp_items_catalog_id_fk')
                ->references('id')
                ->on('projectx_fabric_component_catalog')
                ->onDelete('set null');

            $table->index('fabric_id');
            $table->index(['fabric_id', 'sort_order']);
            $table->unique(['fabric_id', 'fabric_component_catalog_id', 'label_override'], 'projectx_fabric_composition_unique');
        });
        }

        $now = now();
        if (DB::table('projectx_fabric_component_catalog')->count() === 0) {
        DB::table('projectx_fabric_component_catalog')->insert([
            [
                'business_id' => null,
                'label' => 'Cotton',
                'aliases' => json_encode([]),
                'default_unit' => 'percent',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Polyester',
                'aliases' => json_encode(['poly', 'polyester']),
                'default_unit' => 'percent',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Spandex',
                'aliases' => json_encode(['elastane', 'lycra', 'spandex']),
                'default_unit' => 'percent',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Nylon',
                'aliases' => json_encode([]),
                'default_unit' => 'percent',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Rayon/Viscose',
                'aliases' => json_encode(['rayon', 'viscose']),
                'default_unit' => 'percent',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Wool',
                'aliases' => json_encode([]),
                'default_unit' => 'percent',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Linen',
                'aliases' => json_encode([]),
                'default_unit' => 'percent',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Acrylic',
                'aliases' => json_encode([]),
                'default_unit' => 'percent',
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Silk',
                'aliases' => json_encode([]),
                'default_unit' => 'percent',
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Modal',
                'aliases' => json_encode([]),
                'default_unit' => 'percent',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Lyocell/TENCEL',
                'aliases' => json_encode(['lyocell', 'tencel']),
                'default_unit' => 'percent',
                'sort_order' => 11,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => null,
                'label' => 'Other',
                'aliases' => json_encode(['other']),
                'default_unit' => 'percent',
                'sort_order' => 99,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('projectx_fabric_composition_items');
        Schema::dropIfExists('projectx_fabric_component_catalog');
    }
}