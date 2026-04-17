<?php

namespace Tests\Unit;

use Tests\TestCase;

class CmsBlogDetailDomContractTest extends TestCase
{
    public function test_blog_detail_blade_keeps_crafto_contract_markers_in_order(): void
    {
        $viewPath = base_path('Modules/Cms/Resources/views/frontend/blogs/show.blade.php');
        $this->assertFileExists($viewPath);

        $markup = file_get_contents($viewPath);
        $this->assertIsString($markup);

        $markers = [
            'page-title-center-alignment cover-background top-space-padding',
            'top-space-margin half-section pb-0',
            'overlap-section text-center p-0 sm-pt-50px',
            'dropcap-style-02 last-paragraph-no-margin',
            'elements-social social-icon-style-04',
            'blog-classic blog-wrapper grid grid-4col',
            'ul class="blog-comment"',
        ];

        $lastPosition = -1;
        foreach ($markers as $marker) {
            $position = strpos($markup, $marker);
            $this->assertNotFalse($position, 'Missing Crafto contract marker: '.$marker);
            $this->assertGreaterThan(
                $lastPosition,
                $position,
                'Crafto marker order drifted at: '.$marker
            );
            $lastPosition = $position;
        }
    }
}

