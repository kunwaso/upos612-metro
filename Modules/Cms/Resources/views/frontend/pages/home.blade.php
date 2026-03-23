@extends('cms::frontend.layouts.app')
@section('title', 'Home')
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')

<!-- Hero Banner -->
<div class="section-hero">
    <div class="hero-image">
        <img src="{{ asset('modules/cms/assets/images/banner/banner-1.png') }}" alt="Hero Banner">
    </div>
    <div class="container">
        <div class="content-wrap text-center">
            <div class="title text-display-2 effectFade fadeRotateX">
                <span class="title1 fw-semibold text-gradient-4">Packaging </span>
                <br>
                <div class="title2 d-flex gap-20 justify-content-center flex-wrap">
                    <span class="fw-semibold text-white">Solutions.</span>
                    
                </div>
            </div>
            <p class="text effectFade fadeUp fs-24">
                Giải pháp về đóng gói, in ấn, và cung cấp các sản phẩm đóng gói cho các doanh nghiệp trong và ngoài nước.
            </p>
            <div class="bot-btns effectFade fadeRotateX">
                <a href="{{ url('shop/page/services') }}" class="tf-btn">
                    Xem chi tiết
                </a>
                <a href="#pricing" class="tf-btn-2">
                    Liên hệ ngay
                </a>
            </div>
        </div>
    </div>
    <a href="#about" class="scroll-more">
        <span class="fw-semibold link1">Cuộn xuống để xem thêm</span>
        <i class="icon icon-long-arrow-alt-down-solid"></i>
    </a>
</div>
<!-- section-Catalog -->
<div id="about" class="section-benefits flat-spacing pt-0">
    <div class="container">
        <div class="heading-section center mb-70 pt-30">
          
            <div class="heading-title text-gradient-3 effectFade fadeRotateX">Danh mục sản phẩm</div>
        </div>
        <div class="row col-lg-12 mb-30">
            <div class="col-lg-4">
                <div class="benefits-box benefits-secure effectFade fadeup has-transition hov-shadow-out">
                    <i class="catalogicon fa-solid fa-scroll"></i>
                    <div class="benefits-secure-inner text-center">
                        <a href="{{ url('shop/products/bao-bi-cuon') }}"><img src="{{ asset('modules/cms/assets/images/products/rolls/roll-packaging.png') }}" alt=""></a>
                    </div>
                    <div class="content">
                        <h6 class="fw-semibold title">Bao bì dạng cuộn</h6>
                        <p class="text text-secondary">các sản phẩm dạng cuộn như giấy, nhựa, vải, và các vật liệu khác. Phương pháp này giúp bảo quản và vận chuyển sản phẩm một cách an toàn và hiệu quả.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="benefits-box benefits-secure effectFade fadeup has-transition hov-shadow-out">
                    <i class="catalogicon fa-solid fa-box-open"></i>
                    <div class="benefits-secure-inner text-center">
                        <a href="{{ url('shop/products/hop-thung-carton') }}"><img src="{{ asset('modules/cms/assets/images/products/boxes/box-packaging.png') }}" alt=""></a>
                    </div>
                    <div class="content">
                        <h6 class="fw-semibold title">Hộp - Thùng carton</h6>
                        <p class="text text-secondary">các sản phẩm dạng hộp giấy, thùng carton. Phương pháp này giúp bảo quản và vận chuyển sản phẩm một cách an toàn và hiệu quả.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="benefits-box benefits-secure effectFade fadeup has-transition hov-shadow-out">
                    <i class="catalogicon fa-solid fa-link"></i>
                    <div class="benefits-secure-inner text-center">
                        <a href="{{ url('shop/products/day-dai') }}"><img src="{{ asset('modules/cms/assets/images/products/strings/string-packaging.png') }}" alt=""></a>
                    </div>
                    <div class="content">
                        <h6 class="fw-semibold title">Dây đai</h6>
                        <p class="text text-secondary">các sản phẩm dạng dây đai từ nhựa và các vật liệu khác.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-24">
            <div class="col-lg-7">
                <div class="benefits-box benefits-progress  effectFade fadeup has-transition hov-shadow-out">
                    <i class="catalogicon fa-solid fa-soap"></i>
                    {{-- <div class="benefits-secure-inner text-center">
                    <a href="{{ url('shop/page/products') }}"><img src="{{ asset('modules/cms/assets/images/products/air/air-packaging.png') }}" alt=""></a>
                    </div> --}}
                    <div class="benefits-secure-inner text-center">
                        <a href="{{ url('shop/page/products') }}"><img src="{{ asset('modules/cms/assets/images/products/strings/string-packaging.png') }}" alt=""></a>
                    </div>
                    <div class="content">
                        <h6 class="fw-semibold title">Túi chống sốc - silica-gel</h6>
                        <p class="text text-secondary">các sản phẩm dạng túi chống sốc và silica gel bảo quản sản phẩm khô ráo chống va đập</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="benefits-box benefits-step effectFade fadeup has-transition hov-shadow-out">
                    <i class="catalogicon fa-solid fa-boxes-packing"></i>
                    <div class="benefits-secure-inner text-center">
                        <a href="{{ url('shop/page/products') }}"><img src="{{ asset('modules/cms/assets/images/products/other/other-packaging.png') }}" alt=""></a>
                    </div>
                    <div class="content">
                        <h6 class="fw-semibold title">Sản phẩm khác</h6>
                        <p class="text text-secondary">các sản phẩm dạng kệ, pallet, dụng cụ đóng gói hỗ trợ đóng gói bảo quản</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- /section-benefits -->


<div class="box-white">
    
    <!-- section-features -->
    <div class="section-features flat-spacing pt-0">
        <div class="container pt-30">
            <div class="heading-section center mb-64 mt-30">
                <div class="heading-title text-gradient-3 effectFade fadeRotateX">Dịch vụ</div>
            </div>
        </div>
        <div class="position-relative">
            <div class="container z-5">
                <div class="features-wrap justify-content-between">
                    <div class="features-col col-left lg-mb-24">
                        <div class="features-item effectFade fadeUp">
                            <i class="icon icon-robot-solid"></i>
                            {{-- <img class="image-service img-fluid" src="{{ asset('modules/cms/assets/images/services/design-service.jpg') }}" alt=""> --}}
                            <h6 class="title fw-semibold">Thiết kế nhãn hiệu</h6>
                            <p class="text-secondary">
                                Thiết kế nhãn hiệu đẹp và hiệu quả.
                            </p>
                        </div>
                        <div class="features-item effectFade fadeUp">
                            <i class="icon icon-clipboard-check-solid"></i>
                            <h6 class="title fw-semibold">In ấn nhãn hiệu</h6>
                            <p class="text-secondary">
                                In ấn nhãn hiệu trên vật liệu đóng gói, phù hợp với nhu cầu của bạn.
                            </p>
                        </div>
                        <div class="features-item effectFade fadeUp">
                            <i class="icon icon-book-solid"></i>
                            <h6 class="title fw-semibold">Thi công đóng gói</h6>
                            <p class="text-secondary">
                                Bạn đang tìm kiếm công ty thi công đóng gói hàng hóa theo yêu cầu của bạn? Chúng tôi cung cấp dịch vụ thi công đóng gói hàng hóa theo yêu cầu của bạn.
                            </p>
                        </div>
                    </div>
                    <div class="features-center flex-shrink">
                        <img src="{{ asset('modules/cms/assets/images/logo/logo-1.svg') }}" alt="">
                    </div>
                    <div class="features-col col-right">
                        <div class="features-item effectFade fadeUp" data-delay="0.1">
                            <i class="icon icon-user-check-solid"></i>
                            <h6 class="title fw-semibold">In ấn thùng carton</h6>
                            <p class="text-secondary">
                                In ấn thùng carton trên vật liệu đóng gói chi tiết sản phẩm
                            </p>
                        </div>
                        <div class="features-item effectFade fadeUp" data-delay="0.1">
                            <i class="icon icon-shield-alt-solid"></i>
                            <h6 class="title fw-semibold">Secure by Design</h6>
                            <p class="text-secondary">
                                PII handling, SSO/SAML, RBAC, secrets management, and compliance workflows—ship AI that’s safe, auditable, and enterprise-ready.
                            </p>
                        </div>
                        <div class="features-item effectFade fadeUp" data-delay="0.1">
                            <i class="icon icon-plug-solid"></i>
                            <h6 class="title fw-semibold">Seamless Integrations</h6>
                            <p class="text-secondary">
                                Plug into your stack (CRM, helpdesk, ERP, data warehouse) with webhooks and APIs to turn insights into action—fast.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="side-line-main d-none d-lg-block wow fadeIn">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-4 mx-auto">
                            <div class="side-line-wrap">
                                <div class="link-break-line left">
                                    <div class="link-break-line">
                                        <span class="item top"></span>
                                        <span class="item bottom"></span>
                                    </div>
                                </div>
                                <div class="link-break-center">
                                    <span class="simu-electric left"></span>
                                    <span class="simu-electric right"></span>
                                </div>
                                <div class="link-break-line right">
                                    <span class="item top"></span>
                                    <span class="item bottom"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /section-features -->
    <!-- section-featured-works -->
    <div id="works" class="section-featured-works flat-spacing pt-0">
        <div class="container">
            <div class="heading-section mb-0">
                <div class="heading-sub fw-semibold mx-auto effectFade fadeUp">Featured Works</div>
            </div>
            <div class="featured-works-list position-relative">
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                    <div class="featured-works-item  effectFade fadeUp no-div">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-1.jpg') }}" alt="">
                            <a href="{{ url('shop/page/works') }}" class="tf-mouse view-project h6">
                                View Project
                                <i class="icon icon-arrow-top-right"></i>
                            </a>
                        </div>
                        <div class="content">
                            <div class="pagi-dot">
                                <span class="active"></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="bot">
                                <h4 class="heading fw-semibold">Support Copilot <br> for SaaS</h4>
                                <div class="grid-text">
                                    <div class="item">
                                        <div class="title text-secondary">DESCRIPTION</div>
                                        <div class="text-body-3 fw-semibold">Draft replies and pulls account context; reduced first-response time by 38%.</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">DELIVERABLES</div>
                                        <div class="fw-semibold text-body-3">AI strategy, AI UX flows, <br> LLM agent, RAG</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">INDUSTRY</div>
                                        <div class="fw-semibold text-body-3">SaaS</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="featured-works-item ">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-2.jpg') }}" alt="">
                            <a href="{{ url('shop/page/works') }}" class="tf-mouse view-project h6">
                                View Project
                                <i class="icon icon-arrow-top-right"></i>
                            </a>
                        </div>
                        <div class="content">
                            <div class="pagi-dot">
                                <span></span>
                                <span class="active"></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="bot">
                                <h4 class="heading fw-semibold">Underwriting <br> Risk Copilot</h4>
                                <div class="grid-text">
                                    <div class="item">
                                        <div class="title text-secondary">DESCRIPTION</div>
                                        <div class="text-body-3 fw-semibold">Built a triage assistant to summarize claims; cut manual review time by 42%.</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">DELIVERABLES</div>
                                        <div class="fw-semibold">Use-case mapping, Prompt & UI patterns</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">INDUSTRY</div>
                                        <div class="fw-semibold">Fintech</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="featured-works-item ">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-3.jpg') }}" alt="">
                            <a href="{{ url('shop/page/works') }}" class="tf-mouse view-project h6">
                                View Project
                                <i class="icon icon-arrow-top-right"></i>
                            </a>
                        </div>
                        <div class="content">
                            <div class="pagi-dot">
                                <span></span>
                                <span></span>
                                <span class="active"></span>
                                <span></span>
                            </div>
                            <div class="bot">
                                <h4 class="heading fw-semibold">Clinical Note <br> Summarizer</h4>
                                <div class="grid-text">
                                    <div class="item">
                                        <div class="title text-secondary">DESCRIPTION</div>
                                        <div class="text-body-3 fw-semibold">Clinic-lobby assistant answering pre-visit questions; decreased front-desk calls by 28%.</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">DELIVERABLES</div>
                                        <div class="fw-semibold">PHI-safe RAG, HIPAA-aligned workflows</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">INDUSTRY</div>
                                        <div class="fw-semibold">Healthcare</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="featured-works-item ">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-4.jpg') }}" alt="">
                            <a href="{{ url('shop/page/works') }}" class="tf-mouse view-project h6">
                                View Project
                                <i class="icon icon-arrow-top-right"></i>
                            </a>
                        </div>
                        <div class="content">
                            <div class="pagi-dot">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span class="active"></span>
                            </div>
                            <div class="bot">
                                <h4 class="heading fw-semibold">Catalog Intelligence <br> Engine</h4>
                                <div class="grid-text">
                                    <div class="item">
                                        <div class="title text-secondary">DESCRIPTION</div>
                                        <div class="text-body-3 fw-semibold">Launched a shopping copilot that understands attributes; raised add-to-cart by 12%.</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">DELIVERABLES</div>
                                        <div class="fw-semibold">Data cleaning & embeddings</div>
                                    </div>
                                    <div class="item">
                                        <div class="title text-secondary">INDUSTRY</div>
                                        <div class="fw-semibold">Ecommerce/Retail</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /section-featured-works -->

</div>

<!-- section-tools -->
<div class="section-tools flat-spacing">
    <img class="img-1 img-grow-1" src="{{ asset('modules/cms/assets/images/item/item-4.svg') }}" alt="">
    <img class="img-2 img-grow-2" src="{{ asset('modules/cms/assets/images/item/item-5.svg') }}" alt="">
    <img class="img-3 img-grow-3" src="{{ asset('modules/cms/assets/images/item/item-6.svg') }}" alt="">
    <img class="img-4 img-grow-4" src="{{ asset('modules/cms/assets/images/item/item-7.svg') }}" alt="">
    <img class="img-5 img-grow-5" src="{{ asset('modules/cms/assets/images/item/item-8.svg') }}" alt="">
    <img class="img-6 img-grow-6" src="{{ asset('modules/cms/assets/images/item/item-9.svg') }}" alt="">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-md-8 text-center">
                <div class="heading-section center mb-48">
                    <div class="heading-sub fw-semibold effectFade fadeUp">Tools</div>
                    <div class="heading-title text-gradient-3 effectFade fadeRotateX">We work with powerful AI tools</div>
                </div>
                <div class="text effectFade fadeUp">
                    We design, build, and evaluate with a modern AI stack—LLMs, vector search, orchestration, and observability—so your features are fast, reliable, and secure.
                </div>
                <a href="{{ route('cms.contact.us') }}" class="tf-btn effectFade fadeRotateX">
                    Get Started
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /section-tools -->


<!-- section-new arrivals -->
<div class="section-catalog flat-spacing pt-10">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="process-heading h-100">
                    <div class="heading-section mb-80">
                        <div class="heading-sub fw-semibold effectFade fadeUp">Catalog</div>
                        <div class="heading-title text-gradient-3 effectFade fadeRotateX">Sản phẩm mới</div>
                    </div>
                    <div class="group-btn-slider">
                        <div class="nav-prev-swiper">
                            <i class="icon icon-angle-left-solid"></i>
                        </div>
                        <div class="nav-next-swiper">
                            <i class="icon icon-angle-right-solid"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="process-slide">
                    <div dir="ltr" class="swiper tf-swiper swiper-box-shadow" data-preview="1.78" data-tablet="2" data-mobile-sm="1" data-mobile="1"
                        data-loop="false" data-center="false" data-space-lg="24" data-space-md="24" data-space="30" >
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="process-card">
                                    <i class="icon fa-solid fa-scroll"></i>
                                    <div class="content">
                                        <h4 class="title fw-semibold">Cuộn (Roll Pack)</h4>
                                        <p class="text text-secondary">các sản phẩm dạng cuộn như giấy, nhựa, vải, và các vật liệu khác. Phương pháp này giúp bảo quản và vận chuyển sản phẩm một cách an toàn và hiệu quả.</p>
                                    </div>
                                    <div class="bot">
                                        <img src="{{ asset('modules/cms/assets/images/products/rolls/roll-packaging.png') }}" alt="">
                                        <div class="number">
                                            <span class="text-neutral-400">01</span>
                                            <span class="text-neutral-200">/03</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="process-card">
                                    <i class="icon fas fa-box-open"></i>
                                    <div class="content">
                                        <h4 class="title fw-semibold">Hộp (Box Pack)</h4>
                                        <p class="text text-secondary">các sản phẩm dạng hộp giấy. Phương pháp này giúp bảo quản và vận chuyển sản phẩm một cách an toàn và hiệu quả.</p>
                                    </div>
                                    <div class="bot">
                                        <img src="{{ asset('modules/cms/assets/images/products/boxes/box-packaging.png') }}" alt="">
                                        <div class="number">
                                            <span class="text-neutral-400">02</span>
                                            <span class="text-neutral-200">/03</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="process-card">
                                    <i class="icon icon-user-check-solid-1"></i>
                                    <div class="content">
                                        <h4 class="title fw-semibold">Validate & Evals</h4>
                                        <p class="text text-secondary">Prove accuracy, usability, safety, and cost. Eval dashboard, acceptance thresholds, decision to iterate/ship.</p>
                                    </div>
                                    <div class="bot">
                                        <div class="time fw-semibold">1 WEEKS</div>
                                        <div class="number">
                                            <span class="text-neutral-400">03</span>
                                            <span class="text-neutral-200">/03</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /section-new arrivals -->

<!-- section-contact -->
<div id="contact" class="flat-spacing pt-0">
    <div class="section-contact">
        <div class="contact-image">
            <img src="{{ asset('modules/cms/assets/images/section/contact-image-bg.jpg') }}" alt="">
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="col-left">
                        <div class="heading-section mb-48">
                            <div class="heading-sub fw-semibold effectFade fadeUp">Liên hệ</div>
                            <div class="heading-title text-gradient-3 effectFade fadeRotateX">
                                Liên hệ ngay <br> để được hỗ trợ
                            </div>
                        </div>
                        <div>
                            <div class="contact-item mb-20 effectFade fadeRotateX">
                                <i class="icon icon-envelope-solid"></i>
                                <div class="content">
                                    <div class="title fw-semibold mb-2">E-mail Địa chỉ</div>
                                    <div class="text">sales@packsolut.com</div>
                                </div>
                            </div>
                            <div class="contact-item effectFade fadeRotateX" data-delay="0.1">
                                <i class="icon icon-headset-solid"></i>
                                <div class="content">
                                    <div class="title fw-semibold mb-2">Hotline</div>
                                    <div class="text">+84 (933) 662 962</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <form class="form-contact effectFade fadeUp">
                        <h4 class="heading fw-semibold">Fill this form below</h4>
                        <fieldset class="mb-21">
                            <label class="fw-semibold text-body-3 mb-20">Your Name</label>
                            <input class="" type="text" placeholder="Enter your full name" required>
                        </fieldset>
                        <fieldset class="mb-21">
                            <label class="fw-semibold text-body-3 mb-20">Your Phone</label>
                            <input class="" type="text" placeholder="Enter the e-mail" required>
                        </fieldset>
                        <fieldset class="mb-18">
                            <label class="fw-semibold text-body-3 mb-0">More About The Project</label>
                            <textarea name="text" class=""></textarea>
                        </fieldset>
                        <div class="attachment d-flex gap-8 align-items-center">
                            <i class="icon icon-paperclip-solid fs-24"></i>
                            <div class="fw-semibold text-body-3">Add an Attachment</div>
                        </div>
                        <button type="submit" class="tf-btn w-100">Submit Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /section-contact -->

@endsection
@section('javascript')
@endsection