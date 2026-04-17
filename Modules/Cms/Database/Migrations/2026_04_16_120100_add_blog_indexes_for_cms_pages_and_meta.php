<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->index(['type', 'is_enabled', 'priority'], 'cms_pages_type_enabled_priority_idx');
        });

        Schema::table('cms_page_metas', function (Blueprint $table) {
            $table->index(['cms_page_id', 'meta_key'], 'cms_page_metas_page_key_idx');
        });

        Schema::table('cms_site_details', function (Blueprint $table) {
            $table->index('site_key', 'cms_site_details_site_key_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropIndex('cms_pages_type_enabled_priority_idx');
        });

        Schema::table('cms_page_metas', function (Blueprint $table) {
            $table->dropIndex('cms_page_metas_page_key_idx');
        });

        Schema::table('cms_site_details', function (Blueprint $table) {
            $table->dropIndex('cms_site_details_site_key_idx');
        });
    }
};
