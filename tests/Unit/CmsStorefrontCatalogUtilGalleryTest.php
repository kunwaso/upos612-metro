<?php

namespace Tests\Unit;

use App\Media;
use App\Product;
use App\Variation;
use Modules\Cms\Utils\CmsStorefrontCatalogUtil;
use Tests\TestCase;

class CmsStorefrontCatalogUtilGalleryTest extends TestCase
{
    public function test_build_gallery_urls_includes_main_then_variation_images_and_skips_pdf_media(): void
    {
        /** @var CmsStorefrontCatalogUtil $catalogUtil */
        $catalogUtil = $this->app->make(CmsStorefrontCatalogUtil::class);

        $jpg = Media::make([
            'file_name' => '100_a_photo.jpg',
            'business_id' => 1,
        ]);
        $pdf = Media::make([
            'file_name' => '200_doc.pdf',
            'business_id' => 1,
        ]);

        $variation = Variation::make(['id' => 5]);
        $variation->setRelation('media', collect([$jpg]));

        $product = Product::make([
            'image' => 'main.png',
            'business_id' => 1,
        ]);
        $product->setRelation('media', collect([$pdf]));
        $product->setRelation('variations', collect([$variation]));

        $urls = $catalogUtil->buildGalleryUrls($product);

        $this->assertCount(2, $urls);
        $this->assertStringContainsString('main.png', $urls[0]);
        $this->assertStringContainsString('100_a_photo.jpg', rawurldecode($urls[1]));
    }

    public function test_build_gallery_urls_falls_back_to_default_when_no_images(): void
    {
        /** @var CmsStorefrontCatalogUtil $catalogUtil */
        $catalogUtil = $this->app->make(CmsStorefrontCatalogUtil::class);

        $product = Product::make([
            'image' => null,
            'business_id' => 1,
        ]);
        $product->setRelation('media', collect([]));
        $product->setRelation('variations', collect([]));

        $urls = $catalogUtil->buildGalleryUrls($product);

        $this->assertCount(1, $urls);
        $this->assertStringContainsString('default.png', $urls[0]);
    }
}
