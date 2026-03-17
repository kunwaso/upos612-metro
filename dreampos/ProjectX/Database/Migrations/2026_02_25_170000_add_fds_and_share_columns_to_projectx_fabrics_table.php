<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFdsAndShareColumnsToProjectxFabricsTable extends Migration
{
    public function up()
    {
        Schema::table('projectx_fabrics', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_fabrics', 'fds_date')) {
                $table->date('fds_date')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'swatch_submit_date')) {
                $table->date('swatch_submit_date')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'season_department')) {
                $table->string('season_department')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'pattern_color_name_number')) {
                $table->string('pattern_color_name_number')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'mill_pattern_color')) {
                $table->string('mill_pattern_color')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'performance_claims')) {
                $table->text('performance_claims')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'dyeing_technique')) {
                $table->string('dyeing_technique')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'submit_type')) {
                $table->string('submit_type')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'construction_ypi')) {
                $table->string('construction_ypi')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'fabric_finish')) {
                $table->string('fabric_finish')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'care_label')) {
                $table->text('care_label')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'elongation')) {
                $table->string('elongation')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'growth')) {
                $table->string('growth')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'recovery')) {
                $table->string('recovery')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'elongation_25_fixed')) {
                $table->string('elongation_25_fixed')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'wool_type')) {
                $table->string('wool_type')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'raw_material_origin')) {
                $table->string('raw_material_origin')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'dyeing_type')) {
                $table->string('dyeing_type')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'fds_season')) {
                $table->string('fds_season')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'price_500_yds')) {
                $table->decimal('price_500_yds', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'price_3k')) {
                $table->decimal('price_3k', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'price_10k')) {
                $table->decimal('price_10k', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'price_25k')) {
                $table->decimal('price_25k', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'price_50k_plus')) {
                $table->decimal('price_50k_plus', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'minimum_color_quantity')) {
                $table->decimal('minimum_color_quantity', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'monthly_capacity')) {
                $table->decimal('monthly_capacity', 22, 4)->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'share_enabled')) {
                $table->boolean('share_enabled')->default(false)->index();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'share_token')) {
                $table->string('share_token', 64)->nullable()->unique();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'share_password_hash')) {
                $table->string('share_password_hash')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'share_expires_at')) {
                $table->timestamp('share_expires_at')->nullable();
            }

            if (! Schema::hasColumn('projectx_fabrics', 'share_rate_limit_per_day')) {
                $table->unsignedInteger('share_rate_limit_per_day')->nullable();
            }
        });
    }

    public function down()
    {
        $columns = [
            'fds_date',
            'swatch_submit_date',
            'season_department',
            'pattern_color_name_number',
            'mill_pattern_color',
            'performance_claims',
            'dyeing_technique',
            'submit_type',
            'construction_ypi',
            'fabric_finish',
            'care_label',
            'elongation',
            'growth',
            'recovery',
            'elongation_25_fixed',
            'wool_type',
            'raw_material_origin',
            'dyeing_type',
            'fds_season',
            'price_500_yds',
            'price_3k',
            'price_10k',
            'price_25k',
            'price_50k_plus',
            'minimum_color_quantity',
            'monthly_capacity',
            'share_enabled',
            'share_token',
            'share_password_hash',
            'share_expires_at',
            'share_rate_limit_per_day',
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
