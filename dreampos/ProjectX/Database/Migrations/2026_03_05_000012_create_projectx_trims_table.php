<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxTrimsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('projectx_trims')) {
            return;
        }

        Schema::create('projectx_trims', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('trim_category_id')->nullable();

            $table->string('name');
            $table->string('part_number')->nullable();
            $table->text('description')->nullable();

            $table->string('material')->nullable();
            $table->string('color_value')->nullable();
            $table->string('size_dimension')->nullable();
            $table->string('unit_of_measure', 50)->default('pcs');

            $table->string('placement')->nullable();
            $table->decimal('quantity_per_garment', 22, 4)->nullable();

            $table->unsignedInteger('supplier_contact_id')->nullable();
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->string('currency', 20)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();

            $table->text('care_testing')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('qc_at')->nullable();
            $table->text('qc_notes')->nullable();

            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            // Optional datasheet attributes kept nullable for progressive adoption.
            $table->string('category_group')->nullable();
            $table->string('label_sub_type')->nullable();
            $table->string('purpose')->nullable();
            $table->string('button_ligne')->nullable();
            $table->string('button_holes')->nullable();
            $table->string('button_material')->nullable();
            $table->string('zipper_type')->nullable();
            $table->string('zipper_slider')->nullable();
            $table->string('interlining_type')->nullable();
            $table->text('quality_notes')->nullable();
            $table->string('shrinkage')->nullable();
            $table->string('rust_proof')->nullable();
            $table->string('comfort_notes')->nullable();

            $table->timestamps();

            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');
            $table->foreign('trim_category_id')
                ->references('id')
                ->on('projectx_trim_categories')
                ->onDelete('set null');
            $table->foreign('supplier_contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('set null');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('business_id');
            $table->index('status');
            $table->index('trim_category_id');
            $table->index('supplier_contact_id');
            $table->index('part_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projectx_trims');
    }
}
