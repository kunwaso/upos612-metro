<?php

namespace Modules\Cms\Utils;

use App\Business;
use App\Product;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorConcrete;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CmsStorefrontCatalogUtil
{
    /** @var Business|null */
    protected ?Business $businessCache = null;

    public function __construct(protected Util $util)
    {
    }

    public function getStorefrontBusinessId(): ?int
    {
        $id = config('cms.storefront_business_id');

        return $id !== null && $id !== '' ? (int) $id : null;
    }

    public function getStorefrontBusiness(): ?Business
    {
        if ($this->businessCache !== null) {
            return $this->businessCache;
        }
        $id = $this->getStorefrontBusinessId();
        if ($id === null) {
            return null;
        }
        $this->businessCache = Business::query()->find($id);

        return $this->businessCache;
    }

    public function catalogQuery(int $businessId): Builder
    {
        return Product::query()
            ->where('products.business_id', $businessId)
            ->active()
            ->productForSales()
            ->with(['variations']);
    }

    public function paginateCatalog(Request $request, int $perPage = 12): LengthAwarePaginator
    {
        $businessId = $this->getStorefrontBusinessId();
        if ($businessId === null || $this->getStorefrontBusiness() === null) {
            return $this->emptyPaginator($request, $perPage);
        }

        $sort = $request->query('sort');
        if (! in_array($sort, ['latest', 'name'], true)) {
            $sort = 'latest';
        }

        $query = $this->catalogQuery($businessId);
        $query->reorder();
        if ($sort === 'name') {
            $query->orderBy('products.name');
        } else {
            $query->orderByDesc('products.updated_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getHomeProducts(int $limit = 12): Collection
    {
        $businessId = $this->getStorefrontBusinessId();
        if ($businessId === null || $this->getStorefrontBusiness() === null) {
            return collect();
        }

        return $this->catalogQuery($businessId)
            ->orderByDesc('products.updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getSidebarPreviewProducts(int $limit = 9): Collection
    {
        $businessId = $this->getStorefrontBusinessId();
        if ($businessId === null || $this->getStorefrontBusiness() === null) {
            return collect();
        }

        return $this->catalogQuery($businessId)
            ->orderByDesc('products.updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRelatedProductCards(Product $exclude, int $limit = 4): Collection
    {
        $businessId = $this->getStorefrontBusinessId();
        $business = $this->getStorefrontBusiness();
        if ($businessId === null || $business === null) {
            return collect();
        }

        $products = $this->catalogQuery($businessId)
            ->where('products.id', '!=', $exclude->id)
            ->orderByDesc('products.updated_at')
            ->limit($limit)
            ->get();

        return $products->map(fn (Product $p) => $this->buildCardPresentation($p, $business))->values();
    }

    protected function emptyPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));

        return new PaginatorConcrete(
            collect(),
            0,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'page']
        );
    }

    public function findProductForStorefront(int $businessId, int $productId): ?Product
    {
        /** @var Product|null $product */
        $product = $this->catalogQuery($businessId)
            ->where('products.id', $productId)
            ->with(['media'])
            ->first();

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCardPresentation(Product $product, ?Business $business): array
    {
        $business = $business ?? $this->getStorefrontBusiness();
        $priceData = $this->resolvePriceRange($product);

        return [
            'name' => $product->name,
            'url' => route('cms.store.product.show', ['id' => $product->id]),
            'image_url' => $product->image_url,
            'price_label' => $this->formatPriceLabel($priceData, $business, $priceData['show_from']),
            'show_from_prefix' => $priceData['show_from'],
            'is_new' => $this->isNewProduct($product),
        ];
    }

    /**
     * @return array{min: float|null, max: float|null, show_from: bool}
     */
    protected function resolvePriceRange(Product $product): array
    {
        $prices = [];
        foreach ($product->variations as $v) {
            $p = $v->sell_price_inc_tax ?? $v->default_sell_price;
            if ($p === null) {
                continue;
            }
            $n = (float) $p;
            if ($n > 0) {
                $prices[] = $n;
            }
        }
        if ($prices === []) {
            return ['min' => null, 'max' => null, 'show_from' => false];
        }
        $min = min($prices);
        $max = max($prices);
        $unique = array_unique(array_map(static fn ($x) => round($x, 4), $prices));

        return [
            'min' => $min,
            'max' => $max,
            'show_from' => count($unique) > 1,
        ];
    }

    protected function formatPriceLabel(array $priceData, ?Business $business, bool $showFrom): string
    {
        if ($priceData['min'] === null || $business === null) {
            return __('cms::lang.storefront_price_on_request');
        }
        $formatted = $this->util->num_f($priceData['min'], true, $business);
        if ($showFrom) {
            return __('cms::lang.storefront_price_from', ['price' => $formatted]);
        }

        return $formatted;
    }

    protected function isNewProduct(Product $product): bool
    {
        if (! $product->created_at) {
            return false;
        }

        return $product->created_at->greaterThanOrEqualTo(Carbon::now()->subDays(30));
    }

    /**
     * @return list<string>
     */
    public function buildGalleryUrls(Product $product): array
    {
        $urls = $product->media
            ->sortBy('id')
            ->map(fn ($m) => $m->display_url)
            ->filter()
            ->values()
            ->all();
        if ($urls !== []) {
            return array_values(array_unique($urls));
        }

        return [$product->image_url];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDetailPresentation(Product $product, ?Business $business): array
    {
        $business = $business ?? $this->getStorefrontBusiness();
        $priceData = $this->resolvePriceRange($product);
        $description = (string) ($product->product_description ?? '');
        $plainDescription = Str::limit(trim(strip_tags($description)), 2000);

        return [
            'title' => $product->name,
            'sku' => $product->sku,
            'price_label' => $this->formatPriceLabel($priceData, $business, $priceData['show_from']),
            'description_plain' => $plainDescription,
            'show_from_prefix' => $priceData['show_from'],
        ];
    }

    public function buildResultsSummary(LengthAwarePaginator $paginator): string
    {
        if ($paginator->total() === 0) {
            return __('cms::lang.storefront_no_results');
        }

        return __('cms::lang.storefront_showing_results', [
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ]);
    }
}
