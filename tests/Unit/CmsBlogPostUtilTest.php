<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Utils\BlogPostUtil;
use Tests\TestCase;

class CmsBlogPostUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('tags')->nullable();
            $table->string('feature_image')->nullable();
            $table->integer('priority')->nullable();
            $table->integer('created_by')->nullable();
            $table->boolean('is_enabled')->default(1);
            $table->timestamps();
        });

        Schema::create('cms_page_metas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cms_page_id');
            $table->string('meta_key');
            $table->longText('meta_value')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('cms_page_metas');
        Schema::dropIfExists('cms_pages');
        parent::tearDown();
    }

    public function test_upsert_meta_creates_expected_blog_meta_payload(): void
    {
        $post = CmsPage::create([
            'type' => 'blog',
            'title' => 'Draft',
        ]);

        $request = Request::create('/cms/blog', 'POST', [
            'hero_text' => 'Hero intro',
            'meta_title' => 'SEO title',
            'meta_keywords' => 'alpha,beta',
            'category' => 'News',
        ]);

        $util = app(BlogPostUtil::class);
        $util->upsertMeta((int) $post->id, $request);

        $this->assertDatabaseHas('cms_page_metas', [
            'cms_page_id' => $post->id,
            'meta_key' => 'hero_text',
        ]);

        $meta = $util->metaForPost($post);
        $this->assertSame('Hero intro', $meta['hero_text']);
        $this->assertSame('SEO title', $meta['meta_title']);
        $this->assertSame('alpha,beta', $meta['meta_keywords']);
        $this->assertSame('News', $meta['category']);
    }
}
