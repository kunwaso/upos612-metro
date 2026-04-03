<?php

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Entities\CmsSiteDetail;
use Modules\Cms\Notifications\NewLeadGeneratedNotification;
use Modules\Cms\Utils\CmsUtil;
use Notification;

class CmsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $cmsUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(CmsUtil $cmsUtil)
    {
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

        return view('cms::frontend.pages.home')
            ->with(compact('testimonials', 'faqs', 'statistics', 'page'));
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
        $blogs = CmsPage::where('type', 'blog')
                    ->orderBy('priority', 'asc')
                    ->where('is_enabled', 1)
                    ->with('createdBy')
                    ->get();

        return view('cms::frontend.blogs.index')
            ->with(compact('blogs'));
    }

    public function viewBlog(Request $request)
    {
        $id = $this->cmsUtil->findIdFromGivenUrl($request->url());

        $blog = CmsPage::where('type', 'blog')
                    ->where('is_enabled', 1)
                    ->with('createdBy')
                    ->findOrFail($id);

        return view('cms::frontend.blogs.show')
            ->with(compact('blog'));
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

    public function shopCatalog()
    {
        return view('cms::frontend.pages.shop');
    }

    public function shopCollections()
    {
        return view('cms::frontend.pages.collections');
    }

    public function shopProduct()
    {
        return view('cms::frontend.pages.single_product');
    }

    public function shopCart()
    {
        return view('cms::frontend.pages.cart');
    }

    public function shopCheckout()
    {
        return view('cms::frontend.pages.checkout');
    }

    public function shopAccount()
    {
        return view('cms::frontend.pages.account');
    }

    public function shopWishlist()
    {
        return view('cms::frontend.pages.wishlist');
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
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }
}
