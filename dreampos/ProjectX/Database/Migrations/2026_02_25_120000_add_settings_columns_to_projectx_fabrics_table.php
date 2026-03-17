<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettingsColumnsToProjectxFabricsTable extends Migration
{
    public function up()
    {
        Schema::table('projectx_fabrics', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_fabrics', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'supplier_name')) {
                $table->string('supplier_name')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'supplier_code')) {
                $table->string('supplier_code', 100)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'mill_article_no')) {
                $table->string('mill_article_no')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'country_of_origin')) {
                $table->string('country_of_origin')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'fabric_sku')) {
                $table->string('fabric_sku')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'composition')) {
                $table->string('composition')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'construction_type')) {
                $table->string('construction_type', 100)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'construction_type_other')) {
                $table->string('construction_type_other', 100)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'weave_pattern')) {
                $table->string('weave_pattern')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'yarn_count_denier')) {
                $table->string('yarn_count_denier')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'weight_gsm')) {
                $table->decimal('weight_gsm', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'width_cm')) {
                $table->decimal('width_cm', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'shrinkage_percent')) {
                $table->decimal('shrinkage_percent', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'usable_width_inch')) {
                $table->decimal('usable_width_inch', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'price_per_meter')) {
                $table->decimal('price_per_meter', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'currency')) {
                $table->string('currency', 50)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'minimum_order_quantity')) {
                $table->decimal('minimum_order_quantity', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'payment_terms')) {
                $table->string('payment_terms')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'sample_lead_time_days')) {
                $table->unsignedInteger('sample_lead_time_days')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'bulk_lead_time_days')) {
                $table->unsignedInteger('bulk_lead_time_days')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'shipment_mode')) {
                $table->string('shipment_mode', 100)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'port_of_loading')) {
                $table->string('port_of_loading')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'color_fastness')) {
                $table->string('color_fastness')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'abrasion_resistance')) {
                $table->string('abrasion_resistance')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'handfeel_drape')) {
                $table->string('handfeel_drape')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'finish_treatments')) {
                $table->text('finish_treatments')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'certifications')) {
                $table->text('certifications')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'attachments')) {
                $table->json('attachments')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'notification_email')) {
                $table->boolean('notification_email')->default(true);
            }

            if (! Schema::hasColumn('projectx_fabrics', 'notification_phone')) {
                $table->boolean('notification_phone')->default(false);
            }
        });
    }

    public function down()
    {
        $columns = [
            'description',
            'supplier_name',
            'supplier_code',
            'mill_article_no',
            'country_of_origin',
            'fabric_sku',
            'composition',
            'construction_type',
            'construction_type_other',
            'weave_pattern',
            'yarn_count_denier',
            'weight_gsm',
            'width_cm',
            'shrinkage_percent',
            'usable_width_inch',
            'price_per_meter',
            'currency',
            'minimum_order_quantity',
            'payment_terms',
            'sample_lead_time_days',
            'bulk_lead_time_days',
            'shipment_mode',
            'port_of_loading',
            'color_fastness',
            'abrasion_resistance',
            'handfeel_drape',
            'finish_treatments',
            'certifications',
            'attachments',
            'notification_email',
            'notification_phone',
        ];

        Schema::table('projectx_fabrics', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('projectx_fabrics', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
