<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->ensureCmsBlogPostsTable();
        $this->createCmsBlogPostVariantsTableIfMissing();
        $this->createCmsBlogVariantSectionsTableIfMissing();
        $this->createCmsBlogSettingsTableIfMissing();
        $this->createCmsBlogCommentsTableIfMissing();
        $this->createCmsBlogLikesTableIfMissing();
        $this->createCmsBlogAuditLogsTableIfMissing();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_blog_audit_logs');
        Schema::dropIfExists('cms_blog_likes');
        Schema::dropIfExists('cms_blog_comments');
        Schema::dropIfExists('cms_blog_settings');
        Schema::dropIfExists('cms_blog_variant_sections');
        Schema::dropIfExists('cms_blog_post_variants');
        Schema::dropIfExists('cms_blog_posts');
    }

    private function ensureCmsBlogPostsTable(): void
    {
        if (! Schema::hasTable('cms_blog_posts')) {
            Schema::create('cms_blog_posts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('legacy_cms_page_id')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->string('status', 20)->default('draft');
                $table->boolean('is_enabled')->default(true);
                $table->unsignedInteger('priority')->default(0);
                $table->string('feature_image')->nullable();
                $table->boolean('allow_comments')->default(true);
                $table->boolean('show_author_card')->default(true);
                $table->boolean('show_social_share')->default(true);
                $table->boolean('show_related_posts')->default(true);
                $table->unsignedSmallInteger('related_posts_limit')->default(4);
                $table->timestamps();
            });
        }

        Schema::table('cms_blog_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_blog_posts', 'legacy_cms_page_id')) {
                $table->unsignedBigInteger('legacy_cms_page_id')->nullable();
            }
            if (! Schema::hasColumn('cms_blog_posts', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable();
            }
            if (! Schema::hasColumn('cms_blog_posts', 'status')) {
                $table->string('status', 20)->default('draft');
            }
            if (! Schema::hasColumn('cms_blog_posts', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true);
            }
            if (! Schema::hasColumn('cms_blog_posts', 'priority')) {
                $table->unsignedInteger('priority')->default(0);
            }
            if (! Schema::hasColumn('cms_blog_posts', 'feature_image')) {
                $table->string('feature_image')->nullable();
            }
            if (! Schema::hasColumn('cms_blog_posts', 'allow_comments')) {
                $table->boolean('allow_comments')->default(true);
            }
            if (! Schema::hasColumn('cms_blog_posts', 'show_author_card')) {
                $table->boolean('show_author_card')->default(true);
            }
            if (! Schema::hasColumn('cms_blog_posts', 'show_social_share')) {
                $table->boolean('show_social_share')->default(true);
            }
            if (! Schema::hasColumn('cms_blog_posts', 'show_related_posts')) {
                $table->boolean('show_related_posts')->default(true);
            }
            if (! Schema::hasColumn('cms_blog_posts', 'related_posts_limit')) {
                $table->unsignedSmallInteger('related_posts_limit')->default(4);
            }
            if (! Schema::hasColumn('cms_blog_posts', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (! Schema::hasColumn('cms_blog_posts', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        Schema::table('cms_blog_posts', function (Blueprint $table) {
            if (! $this->hasIndex('cms_blog_posts', 'cms_blog_posts_legacy_cms_page_id_unique')) {
                $table->unique('legacy_cms_page_id', 'cms_blog_posts_legacy_cms_page_id_unique');
            }
            if (! $this->hasIndex('cms_blog_posts', 'cms_blog_posts_status_enabled_priority_idx')) {
                $table->index(['status', 'is_enabled', 'priority'], 'cms_blog_posts_status_enabled_priority_idx');
            }
            if (
                Schema::hasTable('users')
                && ! $this->hasForeignKey('cms_blog_posts', 'cms_blog_posts_created_by_foreign')
                && $this->hasCompatibleColumnType('cms_blog_posts', 'created_by', 'users', 'id')
            ) {
                $table->foreign('created_by', 'cms_blog_posts_created_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    private function createCmsBlogPostVariantsTableIfMissing(): void
    {
        if (Schema::hasTable('cms_blog_post_variants')) {
            return;
        }

        Schema::create('cms_blog_post_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cms_blog_post_id');
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->text('hero_text')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['cms_blog_post_id', 'locale'], 'cms_blog_post_variants_post_locale_unique');
            $table->unique(['locale', 'slug'], 'cms_blog_post_variants_locale_slug_unique');
            $table->index(['locale', 'status', 'published_at'], 'cms_blog_post_variants_locale_status_pub_idx');
            $table->foreign('cms_blog_post_id')->references('id')->on('cms_blog_posts')->onDelete('cascade');
        });
    }

    private function createCmsBlogVariantSectionsTableIfMissing(): void
    {
        if (Schema::hasTable('cms_blog_variant_sections')) {
            return;
        }

        Schema::create('cms_blog_variant_sections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cms_blog_post_variant_id');
            $table->string('section_key', 100);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['cms_blog_post_variant_id', 'section_key'], 'cms_blog_variant_sections_variant_key_unique');
            $table->foreign('cms_blog_post_variant_id', 'cms_blog_variant_sections_variant_fk')
                ->references('id')
                ->on('cms_blog_post_variants')
                ->onDelete('cascade');
        });
    }

    private function createCmsBlogSettingsTableIfMissing(): void
    {
        if (Schema::hasTable('cms_blog_settings')) {
            return;
        }

        Schema::create('cms_blog_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('listing_title_en')->nullable();
            $table->string('listing_title_vi')->nullable();
            $table->text('listing_hero_text_en')->nullable();
            $table->text('listing_hero_text_vi')->nullable();
            $table->string('listing_banner_image')->nullable();
            $table->string('listing_meta_title_en')->nullable();
            $table->string('listing_meta_title_vi')->nullable();
            $table->text('listing_meta_description_en')->nullable();
            $table->text('listing_meta_description_vi')->nullable();
            $table->string('listing_meta_keywords_en')->nullable();
            $table->string('listing_meta_keywords_vi')->nullable();
            $table->boolean('show_author')->default(true);
            $table->boolean('show_publish_date')->default(true);
            $table->boolean('show_related_posts')->default(true);
            $table->boolean('show_comments')->default(true);
            $table->boolean('show_likes')->default(true);
            $table->boolean('show_social_share')->default(true);
            $table->boolean('require_comment_approval')->default(true);
            $table->unsignedInteger('posts_per_page')->default(12);
            $table->string('default_locale', 10)->default('en');
            $table->timestamps();
        });
    }

    private function createCmsBlogCommentsTableIfMissing(): void
    {
        if (Schema::hasTable('cms_blog_comments')) {
            return;
        }

        Schema::create('cms_blog_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cms_blog_post_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->text('comment');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('moderated_by')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['cms_blog_post_id', 'status', 'parent_id'], 'cms_blog_comments_post_status_parent_idx');
            $table->foreign('cms_blog_post_id')->references('id')->on('cms_blog_posts')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('cms_blog_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('moderated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createCmsBlogLikesTableIfMissing(): void
    {
        if (Schema::hasTable('cms_blog_likes')) {
            return;
        }

        Schema::create('cms_blog_likes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cms_blog_post_id');
            $table->unsignedBigInteger('cms_blog_post_variant_id');
            $table->string('session_key', 128);
            $table->timestamps();

            $table->unique(['cms_blog_post_variant_id', 'session_key'], 'cms_blog_likes_variant_session_unique');
            $table->foreign('cms_blog_post_id')->references('id')->on('cms_blog_posts')->onDelete('cascade');
            $table->foreign('cms_blog_post_variant_id')->references('id')->on('cms_blog_post_variants')->onDelete('cascade');
        });
    }

    private function createCmsBlogAuditLogsTableIfMissing(): void
    {
        if (Schema::hasTable('cms_blog_audit_logs')) {
            return;
        }

        Schema::create('cms_blog_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cms_blog_post_id')->nullable();
            $table->unsignedInteger('actor_user_id')->nullable();
            $table->string('action', 100);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at'], 'cms_blog_audit_logs_action_created_idx');
            $table->foreign('cms_blog_post_id')->references('id')->on('cms_blog_posts')->nullOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function hasForeignKey(string $tableName, string $constraintName): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.table_constraints')
            ->where('table_schema', $databaseName)
            ->where('table_name', $tableName)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }

    private function hasCompatibleColumnType(
        string $sourceTable,
        string $sourceColumn,
        string $targetTable,
        string $targetColumn
    ): bool {
        $databaseName = DB::getDatabaseName();

        $sourceType = DB::table('information_schema.columns')
            ->where('table_schema', $databaseName)
            ->where('table_name', $sourceTable)
            ->where('column_name', $sourceColumn)
            ->value('column_type');

        $targetType = DB::table('information_schema.columns')
            ->where('table_schema', $databaseName)
            ->where('table_name', $targetTable)
            ->where('column_name', $targetColumn)
            ->value('column_type');

        return $sourceType !== null && $sourceType === $targetType;
    }
};
