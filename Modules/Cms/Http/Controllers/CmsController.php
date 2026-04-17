<?php

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Cms\Http\Requests\StoreCmsStorefrontRfqRequest;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Entities\CmsPageMeta;
use Modules\Cms\Entities\CmsSiteDetail;
use Modules\Cms\Notifications\NewLeadGeneratedNotification;
use Modules\Cms\Utils\CmsStorefrontCatalogUtil;
use Modules\Cms\Utils\CmsStorefrontRfqUtil;
use Modules\Cms\Utils\CmsUtil;

class CmsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $cmsUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        CmsUtil $cmsUtil,
        protected CmsStorefrontCatalogUtil $storefrontCatalogUtil,
        protected CmsStorefrontRfqUtil $storefrontRfqUtil
    ) {
        $this->cmsUtil = $cmsUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $testimonials = $this->cmsUtil->getPageByType('testimonial');
        $page = $this->cmsUtil->getPageByLayout('home');
        $faqs = CmsSiteDetail::getValue('faqs');
        $statistics = CmsSiteDetail::getValue('statistics');

        $business = $this->storefrontCatalogUtil->getStorefrontBusiness();
        $homeProducts = $this->storefrontCatalogUtil->getHomeProducts(12);
        $homeProductCards = $homeProducts->map(function ($product) use ($business) {
            return $this->storefrontCatalogUtil->buildCardPresentation($product, $business);
        })->values();
        $homeSliderCards = $homeProducts->take(3)->map(function ($product) use ($business) {
            return $this->storefrontCatalogUtil->buildCardPresentation($product, $business);
        })->values();
        $featuredCategories = $this->storefrontCatalogUtil->getFeaturedCategories();

        return view('cms::frontend.pages.home')
            ->with(compact(
                'testimonials',
                'faqs',
                'statistics',
                'page',
                'homeProductCards',
                'homeSliderCards',
                'featuredCategories'
            ));
    }

    public function baobicuon()
    {
        $page = $this->cmsUtil->getPageByLayout('products.baobicuon');
        return view('cms::frontend.products.baobicuon')
            ->with(compact('page'));
    }

    public function hopthungcarton()
    {
        $page = $this->cmsUtil->getPageByLayout('products.hopthungcarton');
        return view('cms::frontend.products.hopthungcarton')
            ->with(compact('page'));
    }
    
    public function daydai()
    {
        $page = $this->cmsUtil->getPageByLayout('products.daydai');
        return view('cms::frontend.products.daydai')
            ->with(compact('page'));
    }

    public function airsilicagel()
    {
        $page = $this->cmsUtil->getPageByLayout('products.airsilicagel');
        return view('cms::frontend.products.airsilicagel')
            ->with(compact('page'));
    }
    
    public function sanphamkhac()
    {
        $page = $this->cmsUtil->getPageByLayout('products.sanphamkhac');
        return view('cms::frontend.products.sanphamkhac')
            ->with(compact('page'));
    }
    
    
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('cms::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('cms::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('cms::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function getBlogList()
    {
        $blogSettings = CmsSiteDetail::getValue('blog_settings');
        if (! is_array($blogSettings)) {
            $blogSettings = [];
        }
        $postsPerPage = max(1, (int) ($blogSettings['posts_per_page'] ?? 12));

        $blogs = CmsPage::where('type', 'blog')
                    ->orderBy('priority', 'asc')
                    ->where('is_enabled', 1)
                    ->with('createdBy')
                    ->paginate($postsPerPage);

        return view('cms::frontend.blogs.index')
            ->with(compact('blogs', 'blogSettings'));
    }

    public function viewBlog(Request $request)
    {
        $id = $this->cmsUtil->findIdFromGivenUrl($request->url());

        $blog = CmsPage::where('type', 'blog')
                    ->where('is_enabled', 1)
                    ->with('createdBy')
                    ->findOrFail($id);

        $blogSettings = CmsSiteDetail::getValue('blog_settings');
        if (! is_array($blogSettings)) {
            $blogSettings = [];
        }

        $metaRows = CmsPageMeta::where('cms_page_id', $blog->id)->get();
        $blogMeta = [];
        foreach ($metaRows as $metaRow) {
            $blogMeta[$metaRow->meta_key] = json_decode($metaRow->meta_value, true);
        }

        $relatedBlogs = collect();
        if (! empty($blogSettings['show_related_posts'])) {
            $relatedBlogs = CmsPage::where('type', 'blog')
                ->where('is_enabled', 1)
                ->where('id', '!=', $blog->id)
                ->orderBy('priority', 'asc')
                ->limit(3)
                ->get();
        }

        return view('cms::frontend.blogs.show')
            ->with(compact('blog', 'blogSettings', 'blogMeta', 'relatedBlogs'));
    }

    public function contactUs(Request $request)
    {
        $page = $this->cmsUtil->getPageByLayout('contact');

        return view('cms::frontend.pages.contact_us')
            ->with(compact('page'));
    }

    public function aboutUs(Request $request)
    {
        $page = $this->cmsUtil->getPageByLayout('pages.about_us');
        return view('cms::frontend.pages.about_us')
            ->with(compact('page'));
    }

    public function shopCatalog(\Illuminate\Http\Request $request)
    {
        $business = $this->storefrontCatalogUtil->getStorefrontBusiness();
        $catalogCategory = trim((string) $request->query('category', ''));
        $catalogSearch = trim((string) $request->query('s', ''));
        $products = $this->storefrontCatalogUtil->paginateCatalog($request, 12);
        $products = $products->through(function ($product) use ($business) {
            return $this->storefrontCatalogUtil->buildCardPresentation($product, $business);
        });
        $resultsSummary = $this->storefrontCatalogUtil->buildResultsSummary($products);
        $catalogSort = $request->query('sort');
        if (! in_array($catalogSort, ['latest', 'name'], true)) {
            $catalogSort = 'latest';
        }
        $sidebarProducts = $this->storefrontCatalogUtil->getSidebarPreviewProducts(9);
        $sidebarCards = $sidebarProducts->map(function ($product) use ($business) {
            return $this->storefrontCatalogUtil->buildCardPresentation($product, $business);
        })->values();
        $sidebarSlides = $sidebarCards->chunk(3);

        return view('cms::frontend.pages.shop')
            ->with(compact('products', 'resultsSummary', 'catalogSort', 'sidebarSlides', 'catalogCategory', 'catalogSearch'));
    }

    public function shopCollections()
    {
        $featuredCategories = $this->storefrontCatalogUtil->getFeaturedCategories();

        return view('cms::frontend.pages.collections')
            ->with(compact('featuredCategories'));
    }

    public function shopProductShow(int $id)
    {
        $businessId = $this->storefrontCatalogUtil->getStorefrontBusinessId();
        if ($businessId === null) {
            abort(404);
        }
        $product = $this->storefrontCatalogUtil->findProductForStorefront($businessId, $id);
        if ($product === null) {
            abort(404);
        }
        $business = $this->storefrontCatalogUtil->getStorefrontBusiness();
        $detail = $this->storefrontCatalogUtil->buildDetailPresentation($product, $business);
        $galleryUrls = $this->storefrontCatalogUtil->buildGalleryUrls($product);
        $relatedProductCards = $this->storefrontCatalogUtil->getRelatedProductCards($product, 4);
        $pageTitle = $detail['title'];
        $metaDescription = \Illuminate\Support\Str::limit($detail['description_plain'] ?? '', 160);

        return view('cms::frontend.pages.single_product')
            ->with(compact('detail', 'galleryUrls', 'relatedProductCards', 'pageTitle', 'metaDescription'));
    }

    public function rfqShow(int $id)
    {
        $businessId = $this->storefrontCatalogUtil->getStorefrontBusinessId();
        if ($businessId === null) {
            abort(404);
        }

        $product = $this->storefrontCatalogUtil->findProductForStorefront($businessId, $id);
        if ($product === null) {
            abort(404);
        }

        $business = $this->storefrontCatalogUtil->getStorefrontBusiness();
        $detail = $this->storefrontCatalogUtil->buildDetailPresentation($product, $business);
        $pageTitle = __('cms::lang.storefront_request_quote');
        $metaDescription = __('cms::lang.storefront_request_quote');

        return view('cms::frontend.pages.rfq')
            ->with(compact('detail', 'pageTitle', 'metaDescription'));
    }

    public function rfqStore(StoreCmsStorefrontRfqRequest $request, int $id)
    {
        $businessId = $this->storefrontCatalogUtil->getStorefrontBusinessId();
        if ($businessId === null) {
            abort(404);
        }

        $product = $this->storefrontCatalogUtil->findProductForStorefront($businessId, $id);
        if ($product === null) {
            abort(404);
        }

        $validated = $request->validated();
        $this->storefrontRfqUtil->createRfqAndTodo($businessId, $product->id, $validated, $product->name);

        return redirect()
            ->route('cms.store.product.show', ['id' => $product->id])
            ->with('status', __('cms::lang.storefront_rfq_submitted'));
    }

    public function shopFaq()
    {
        return view('cms::frontend.pages.faq');
    }

    public function postContactForm(Request $request)
    {
        //check if app is in demo & disable action
        $notAllowedInDemo = $this->cmsUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        if ($request->ajax()) {
            try {
                $lead_details = $request->only(['name', 'mobile', 'email', 'message']);

                $recipient = CmsSiteDetail::getValue('notifiable_email');

                if (! empty($recipient) && ! empty($lead_details['message'])) {
                    Notification::route('mail', $recipient)
                        ->notify(new NewLeadGeneratedNotification($lead_details));
                }

                $output = [
                    'success' => true,
                    'msg' => __('cms::lang.we_will_contact_soon'),
                ];
            } catch (\Exception $e) {
                Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }
}
