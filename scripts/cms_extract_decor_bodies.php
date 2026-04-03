<?php

/**
 * One-shot: extract main body from decor-store HTML demos and rewrite assets/links.
 * Run: php scripts/cms_extract_decor_bodies.php
 */

$base = dirname(__DIR__).'/Modules/Cms/Resources/html';
$outDir = dirname(__DIR__).'/Modules/Cms/Resources/views/frontend/pages/_extracted';

if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

// Line ranges: first line after `<!-- end header -->`, exclusive end at `<!-- start footer -->`
$jobs = [
    ['demo-decor-store-shop.html', 306, 772, 'shop_body.txt'],
    ['demo-decor-store-collections.html', 306, 419, 'collections_body.txt'],
    ['demo-decor-store-single-product.html', 306, 1017, 'single_product_body.txt'],
    ['demo-decor-store-cart.html', 306, 731, 'cart_body.txt'],
    ['demo-decor-store-checkout.html', 306, 1103, 'checkout_body.txt'],
    ['demo-decor-store-account.html', 306, 365, 'account_body.txt'],
    ['demo-decor-store-wishlist.html', 306, 507, 'wishlist_body.txt'],
    ['demo-decor-store-faq.html', 306, 1031, 'faq_body.txt'],
    ['demo-decor-store-about.html', 306, 606, 'about_body.txt'],
    ['demo-decor-store-contact.html', 306, 408, 'contact_body.txt'],
    ['demo-decor-store-blog.html', 306, 502, 'blog_index_body.txt'],
    ['demo-decor-store-blog-single-classic.html', 306, 614, 'blog_show_body.txt'],
];

function transformBody(string $html): string
{
    $replacements = [
        'href="demo-decor-store.html"' => 'href="{{ route(\'cms.home\') }}"',
        'href="demo-decor-store-shop.html"' => 'href="{{ route(\'cms.store.shop\') }}"',
        'href="demo-decor-store-collections.html"' => 'href="{{ route(\'cms.store.collections\') }}"',
        'href="demo-decor-store-single-product.html"' => 'href="{{ route(\'cms.store.product\') }}"',
        'href="demo-decor-store-cart.html"' => 'href="{{ route(\'cms.store.cart\') }}"',
        'href="demo-decor-store-checkout.html"' => 'href="{{ route(\'cms.store.checkout\') }}"',
        'href="demo-decor-store-account.html"' => 'href="{{ route(\'cms.store.account\') }}"',
        'href="demo-decor-store-wishlist.html"' => 'href="{{ route(\'cms.store.wishlist\') }}"',
        'href="demo-decor-store-about.html"' => 'href="{{ route(\'cms.about.us\') }}"',
        'href="demo-decor-store-contact.html"' => 'href="{{ route(\'cms.contact.us\') }}"',
        'href="demo-decor-store-blog.html"' => 'href="{{ route(\'cms.blogs.index\') }}"',
        'href="demo-decor-store-faq.html"' => 'href="{{ route(\'cms.store.faq\') }}"',
        'action="https://craftohtml.themezaa.com/search-result.html"' => 'action="#"',
    ];

    $html = str_replace(array_keys($replacements), array_values($replacements), $html);

    // data-src or src="images/...
    $html = preg_replace_callback(
        '/(src|data-at2x)="images\/([^"]+)"/',
        function ($m) {
            return $m[1].'="{{ asset(\'modules/cms/assets/images/'.$m[2].'\') }}"';
        },
        $html
    );

    // background-image: url(images/...)
    $html = preg_replace_callback(
        '/url\((\s*)images\/([^)]+)\)/',
        function ($m) {
            $path = trim($m[2]);

            return 'url('.$m[1].'{{ asset(\'modules/cms/assets/images/'.$path.'\') }})';
        },
        $html
    );

    // Remaining url("images/...") or url('images/...')
    $html = preg_replace_callback(
        '/url\((\s*)([\'"])images\/([^\'")]+)([\'"])\)/',
        function ($m) {
            return 'url('.$m[1].'{{ asset(\'modules/cms/assets/images/'.$m[3].'\') }})';
        },
        $html
    );

    return $html;
}

foreach ($jobs as [$file, $startLine, $endLineExclusive, $outName]) {
    $path = $base.'/'.$file;
    if (! is_readable($path)) {
        fwrite(STDERR, "Skip missing: $path\n");
        continue;
    }
    $lines = file($path);
    $slice = array_slice($lines, $startLine - 1, $endLineExclusive - $startLine);
    $body = transformBody(implode('', $slice));
    file_put_contents($outDir.'/'.$outName, $body);
    echo "Wrote $outName (lines $startLine-".($endLineExclusive - 1).")\n";
}
