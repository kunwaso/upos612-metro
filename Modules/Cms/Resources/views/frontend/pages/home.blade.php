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
            <p class="text effectFade fadeUp">
                Giải pháp về đóng gói, in ấn, và cung cấp các sản phẩm đóng gói cho các doanh nghiệp trong và ngoài nước.
            </p>
            <div class="bot-btns effectFade fadeRotateX">
                <a href="{{ url('c/page/services') }}" class="tf-btn">
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
    <div class="section-catalog flat-spacing pt-10">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="process-heading h-100">
                        <div class="heading-section mb-80">
                            <div class="heading-sub fw-semibold effectFade fadeUp">Catalog</div>
                            <div class="heading-title text-gradient-3 effectFade fadeRotateX">Danh mục sản phẩm</div>
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
    <!-- /section-Catalog -->

<div class="box-white">
    <!-- section-services -->
    <div id="services" class="section-services flat-spacing">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-xxl-4 col-lg-6">
                    <div class="d-flex flex-column justify-content-between h-100">
                        <div class="col-left">
                            <div class="heading-section mb-48">
                                <div class="heading-sub fw-semibold effectFade fadeUp">Services</div>
                                <div class="heading-title text-gradient-3 effectFade fadeRotateX">End-to-End <br> AI Services</div>
                            </div>
                            <p class="effectFade fadeUp">We turn ambiguous AI ideas into production features your users trust—combining strategy, design, engineering, and rigorous evaluation.</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-lg-6">
                    <div class="accordion-faq_list" id="accordion-services">
                        <div class="accordion-faq_item effectFade fadeUp" role="presentation">
                            <div class="accordion-action services-image-btn active-img" data-img="{{ asset('modules/cms/assets/images/section/service-1.jpg') }}" data-bs-target="#faq-1" role="button"
                                data-bs-toggle="collapse" aria-controls="faq-1" aria-expanded="true">
                                <div class="accordion-title">
                                    AI Strategy <br> & Mapping
                                    <div class="text-body-1 num">(01)</div>
                                </div>
                            </div>
                            <div id="faq-1" class="collapse show" data-bs-parent="#accordion-services">
                                <div class="accordion-content">
                                    <div class="text-body-3 text-neutral-300 text">Identify high-ROI use cases and define a realistic, measurable AI roadmap.</div>
                                    <div class="list-tags">
                                        <a href="#" class="tags-item fw-semibold">Stakeholder discovery</a>
                                        <a href="#" class="tags-item fw-semibold">Value model & KPI definition</a>
                                        <a href="#" class="tags-item fw-semibold">Data readiness assessment</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-faq_item effectFade fadeUp" role="presentation">
                            <div class="accordion-action services-image-btn collapsed" data-img="{{ asset('modules/cms/assets/images/section/service-2.jpg') }}" data-bs-target="#faq-2" role="button"
                                data-bs-toggle="collapse" aria-controls="faq-2" aria-expanded="false">
                                <div class="accordion-title">
                                    AI UX <br> & Product Design
                                    <div class="text-body-1 num">(02)</div>
                                </div>
                            </div>
                            <div id="faq-2" class="collapse" data-bs-parent="#accordion-services">
                                <div class="accordion-content">
                                    <div class="text-body-3 text-neutral-300 text">Human-centered flows, prompts, and interfaces that build trust and adoption.</div>
                                    <div class="list-tags">
                                        <a href="#" class="tags-item fw-semibold">Prototype flows</a>
                                        <a href="#" class="tags-item fw-semibold">Prompt UX patterns</a>
                                        <a href="#" class="tags-item fw-semibold">Usability testing with real users</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-faq_item effectFade fadeUp" role="presentation">
                            <div class="accordion-action services-image-btn collapsed" data-img="{{ asset('modules/cms/assets/images/section/service-3.jpg') }}" data-bs-target="#faq-3" role="button"
                                data-bs-toggle="collapse" aria-controls="faq-3" aria-expanded="false">
                                <div class="accordion-title">
                                    LLM / Agent <br> Development
                                    <div class="text-body-1 num">(03)</div>
                                </div>
                            </div>
                            <div id="faq-3" class="collapse" data-bs-parent="#accordion-services">
                                <div class="accordion-content">
                                    <div class="text-body-3 text-neutral-300 text">Domain-specific copilots and agents that plan, execute, and report.</div>
                                    <div class="list-tags">
                                        <a href="#" class="tags-item fw-semibold">Multi-step planning</a>
                                        <a href="#" class="tags-item fw-semibold">Function calling & toolchains</a>
                                        <a href="#" class="tags-item fw-semibold">Guardrails and audit trails</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-faq_item effectFade fadeUp" role="presentation">
                            <div class="accordion-action services-image-btn collapsed" data-img="{{ asset('modules/cms/assets/images/section/service-4.jpg') }}" data-bs-target="#faq-4" role="button"
                                data-bs-toggle="collapse" aria-controls="faq-4" aria-expanded="false">
                                <div class="accordion-title">
                                    Data Engineering <br> & Pipelines
                                    <div class="text-body-1 num">(04)</div>
                                </div>
                            </div>
                            <div id="faq-4" class="collapse" data-bs-parent="#accordion-services">
                                <div class="accordion-content">
                                    <div class="text-body-3 text-neutral-300 text">Reliable data flows from ingestion to features, built for scale and cost control.</div>
                                    <div class="list-tags">
                                        <a href="#" class="tags-item fw-semibold">Data cleaning & chunking</a>
                                        <a href="#" class="tags-item fw-semibold">Hybrid search</a>
                                        <a href="#" class="tags-item fw-semibold">Freshness, citations, and re-ranking</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="services-image effectFade fadeUp">
                    <img src="{{ asset('modules/cms/assets/images/section/service-1.jpg') }}" alt="">
                </div>
            </div>
        </div>
    </div>
    <!-- /section-services -->
    <!-- section-featured-works -->
    <div id="works" class="section-featured-works flat-spacing pt-0">
        <div class="container">
            <div class="heading-section mb-0">
                <div class="heading-sub fw-semibold mx-auto effectFade fadeUp">Featured Works</div>
            </div>
            <div class="featured-works-list position-relative">
                <div class="">
                    <div class="featured-works-item  effectFade fadeUp no-div">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-1.jpg') }}" alt="">
                            <a href="{{ url('c/page/works') }}" class="tf-mouse view-project h6">
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
                <div class="">
                    <div class="featured-works-item ">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-2.jpg') }}" alt="">
                            <a href="{{ url('c/page/works') }}" class="tf-mouse view-project h6">
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
                <div class="">
                    <div class="featured-works-item ">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-3.jpg') }}" alt="">
                            <a href="{{ url('c/page/works') }}" class="tf-mouse view-project h6">
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
                <div class="">
                    <div class="featured-works-item ">
                        <div class="image main-mouse-hover">
                            <img src="{{ asset('modules/cms/assets/images/section/featured-works-4.jpg') }}" alt="">
                            <a href="{{ url('c/page/works') }}" class="tf-mouse view-project h6">
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
    <!-- /section-featured-works -->

    <!-- section-benefits -->
    <div class="section-benefits flat-spacing pt-0">
        <div class="container">
            <div class="heading-section center mb-70">
                <div class="heading-sub fw-semibold effectFade fadeUp">Benefits</div>
                <div class="heading-title text-gradient-3 effectFade fadeRotateX">Why Choose Us</div>
            </div>
            <div class="row mb-24">
                <div class="col-lg-7">
                    <div class="benefits-box benefits-progress">
                        <div class="benefits-progress-inner">
                            <div class="benefits-progress-item">
                                <div class="progress-line" data-progress="60"></div>
                                <div class="progress-text fw-semibold">
                                    <i class="icon icon-bullseye-solid"></i>
                                    Accuracy
                                </div>
                            </div>
                            <div class="benefits-progress-item">
                                <div class="progress-line" data-progress="100"></div>
                                <div class="progress-text fw-semibold">
                                    <i class="icon icon-tachometer-alt-solid"></i>
                                    Latency
                                </div>
                            </div>
                            <div class="benefits-progress-item">
                                <div class="progress-line" data-progress="40"></div>
                                <div class="progress-text fw-semibold">
                                    <i class="icon icon-shield-alt-solid"></i>
                                    Safety
                                </div>
                            </div>
                            <div class="benefits-progress-item">
                                <div class="progress-line" data-progress="80"></div>
                                <div class="progress-text fw-semibold">
                                    <i class="icon icon-coins-solid"></i>
                                    Cost
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <h6 class="fw-semibold title">Outcome over Output</h6>
                            <p class="text text-secondary">We measure what matters - accuracy, latency, safety, and <br> cost - so every sprint ships business value, not just features.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="benefits-box benefits-step effectFade fadeUp">
                        <div class="benefits-step-inner">
                            <div class="line-step"></div>
                            <div class="step-item">
                                <i class="icon icon-check-solid"></i>
                            </div>
                            <div class="step-item">
                                <i class="icon icon-check-solid"></i>
                            </div>
                            <div class="step-item">
                                <i class="icon icon-check-solid"></i>
                            </div>
                        </div>
                        <div class="content">
                            <h6 class="fw-semibold title">Eval-First Reliability</h6>
                            <p class="text text-secondary">From day one, we run offline/online evals, canary tests, and tracing. You see how models perform - and why.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-5">
                    <div class="benefits-box benefits-secure ">
                        <div class="benefits-secure-inner text-center">
                            <img src="{{ asset('modules/cms/assets/images/item/benefits-1.svg') }}" alt="">
                        </div>
                        <div class="content">
                            <h6 class="fw-semibold title">Secure by Design</h6>
                            <p class="text text-secondary">PII handling, SSO/SAML, RBAC, encryption, and audit trails -built in, not bolted on. Enterprise-ready from the start.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="benefits-box benefits-design">
                        <div class="benefits-design-inner">
                            <img class="item-img-1" src="{{ asset('modules/cms/assets/images/item/benefits-2.svg') }}" alt="">
                            <img class="item-img-2 rightleft" src="{{ asset('modules/cms/assets/images/item/benefits-3.png') }}" alt="">
                            <img class="item-img-3 updown" src="{{ asset('modules/cms/assets/images/item/benefits-4.png') }}" alt="">
                        </div>
                        <div class="content">
                            <h6 class="fw-semibold title">Design-Led AI Experiences</h6>
                            <p class="text text-secondary">We craft prompts, interfaces, and guardrails that feel intuitive - so adoption rises and support tickets fall.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /section-benefits -->
    <!-- section-features -->
    <div class="section-features flat-spacing pt-0">
        <div class="container">
            <div class="heading-section center mb-64">
                <div class="heading-sub fw-semibold effectFade fadeUp">Features</div>
                <div class="heading-title text-gradient-3 effectFade fadeRotateX">All Features in One</div>
            </div>
        </div>
        <div class="position-relative">
            <div class="container z-5">
                <div class="features-wrap justify-content-between">
                    <div class="features-col col-left lg-mb-24">
                        <div class="features-item effectFade fadeUp">
                            <i class="icon icon-robot-solid"></i>
                            <h6 class="title fw-semibold">Agent-Powered Workflows</h6>
                            <p class="text-secondary">
                                Turn repetitive tasks into autonomous flows—agents plan, execute, and report with guardrails, audit trails, and clear handoff to humans.
                            </p>
                        </div>
                        <div class="features-item effectFade fadeUp">
                            <i class="icon icon-clipboard-check-solid"></i>
                            <h6 class="title fw-semibold">Eval-First Quality</h6>
                            <p class="text-secondary">
                                Measure accuracy, latency, safety, and cost from day one. Our evals and dashboards keep models reliable and budgets predictable.
                            </p>
                        </div>
                        <div class="features-item effectFade fadeUp">
                            <i class="icon icon-book-solid"></i>
                            <h6 class="title fw-semibold">Private Knowledge RAG</h6>
                            <p class="text-secondary">
                                Make your docs, tickets, and wikis instantly useful with retrieval augmented generation—freshness, citations, and explainability built in.
                            </p>
                        </div>
                    </div>
                    <div class="features-center flex-shrink">
                        <img src="{{ asset('modules/cms/assets/images/logo/logo-1.svg') }}" alt="">
                    </div>
                    <div class="features-col col-right">
                        <div class="features-item effectFade fadeUp" data-delay="0.1">
                            <i class="icon icon-user-check-solid"></i>
                            <h6 class="title fw-semibold">Human-Centered AI UX</h6>
                            <p class="text-secondary">
                                Interfaces, prompts, and error states designed for trust and adoption—so the smart thing is also the obvious thing to do.
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

<div class="box-black">
    <div class="light-box"></div>
    <img class="light-top" src="{{ asset('modules/cms/assets/images/item/light-top.png') }}" alt="">
    <img class="light-bot" src="{{ asset('modules/cms/assets/images/item/light-bot.png') }}" alt="">
    
    <!-- section-team -->
    <div class="section-team flat-spacing">
        <div class="container">
            <div class="heading-section center mb-64">
                <div class="heading-sub fw-semibold style-1 effectFade fadeUp">Team Members</div>
                <div class="heading-title text-white effectFade fadeRotateX">The Squad Shipping <br> Your AI</div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-8 lg-mb-24">
                    <div class="team-item h-100 effectFade fadeUp">
                        <div class="image">
                            <img src="{{ asset('modules/cms/assets/images/team/team-1.jpg') }}" alt="">
                        </div>
                        <a href="#" class="name h6 fw-semibold">Ava Collins</a>
                        <div class="sub text-body-1">Aigocy ’s Design Lead</div>
                        <div class="tf-social justify-content-center">
                            <a href="#" class="social-item">
                                <i class="icon icon-twitter-x"></i>
                            </a>
                            <a href="#" class="social-item">
                                <i class="icon icon-linkedin-in"></i>
                            </a>
                            <a href="#" class="social-item">
                                <i class="icon icon-github"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 lg-mb-24">
                    <div class="team-item style-1 mb-24 effectFade fadeUp">
                        <div class="top">
                            <div class="image">
                                <img src="{{ asset('modules/cms/assets/images/team/team-2.jpg') }}" alt="">
                            </div>
                            <div class="tf-social justify-content-center">
                                <a href="#" class="social-item">
                                    <i class="icon icon-github"></i>
                                </a>
                                <a href="#" class="social-item">
                                    <i class="icon icon-linkedin-in"></i>
                                </a>
                            </div>
                        </div>
                        <a href="#" class="name text-body-1 fw-semibold">Noah Reed</a>
                        <div class="sub">ML/Agent Lead. Builds domain agents</div>
                    </div>
                    <div class="team-item style-1 effectFade fadeUp">
                        <div class="top">
                            <div class="image">
                                <img src="{{ asset('modules/cms/assets/images/team/team-3.jpg') }}" alt="">
                            </div>
                            <div class="tf-social justify-content-center">
                                <a href="#" class="social-item">
                                    <i class="icon icon-github"></i>
                                </a>
                                <a href="#" class="social-item">
                                    <i class="icon icon-linkedin-in"></i>
                                </a>
                            </div>
                        </div>
                        <a href="#" class="name text-body-1 fw-semibold">Lucas Hayes</a>
                        <div class="sub">Solutions Architect. Connects AI to your stack</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="team-item style-1 mb-24 effectFade fadeUp" data-delay="0.1">
                        <div class="top">
                            <div class="image">
                                <img src="{{ asset('modules/cms/assets/images/team/team-4.jpg') }}" alt="">
                            </div>
                            <div class="tf-social justify-content-center">
                                <a href="#" class="social-item">
                                    <i class="icon icon-github"></i>
                                </a>
                                <a href="#" class="social-item">
                                    <i class="icon icon-linkedin-in"></i>
                                </a>
                            </div>
                        </div>
                        <a href="#" class="name text-body-1 fw-semibold">Jordan Brooks</a>
                        <div class="sub">Data Engineer. Secure access policies</div>
                    </div>
                    <div class="team-item style-1 effectFade fadeUp" data-delay="0.1">
                        <div class="top">
                            <div class="image">
                                <img src="{{ asset('modules/cms/assets/images/team/team-5.jpg') }}" alt="">
                            </div>
                            <div class="tf-social justify-content-center">
                                <a href="#" class="social-item">
                                    <i class="icon icon-github"></i>
                                </a>
                                <a href="#" class="social-item">
                                    <i class="icon icon-linkedin-in"></i>
                                </a>
                            </div>
                        </div>
                        <a href="#" class="name text-body-1 fw-semibold">Erin Park</a>
                        <div class="sub">MLOps Engineer. Productionizes with CI/CD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /section-team -->
    <!-- section-statistic -->
    <div class="section-statistic">
        <div class="line"></div>
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <div class="heading-section mb-48">
                        <div class="heading-sub fw-semibold style-1 effectFade fadeUp">Statistic</div>
                        <div class="heading-title text-white effectFade fadeRotateX">Human-centered <br> AI, built for <br> production</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="statistic-slider">
                        <div class="text text-body-1 text-neutral-400 effectFade fadeUp">
                            We shipped our first copilot in 7 weeks and cut support tickets by 31%. The eval dashboards made every decision obvious.
                        </div>
                        <div class="swiper swiper-progressbar">
                            <div class="group-slider effectFade fadeUp">
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progressBar"></div>
                                </div>
                                <div class="group-btn-slider">
                                    <div class="btn-slider progressbar-prev">
                                        <i class="icon icon-angle-left-solid"></i>
                                    </div>
                                    <div class="btn-slider progressbar-next">
                                        <i class="icon icon-angle-right-solid"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-wrapper effectFade fadeUp">
                                <div class="swiper-slide">
                                    <div class="title fw-semibold text-body-1">OUR GROWTH</div>
                                    <div class="statistic-number">
                                        <span class="number text-white fw-semibold">230</span>
                                        <span class="prefix text-brand">K</span>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="title fw-semibold text-body-1">UPTIME FOR KEY FLOWS</div>
                                    <div class="statistic-number">
                                        <span class="number text-white fw-semibold">95</span>
                                        <span class="prefix text-brand">%</span>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="title fw-semibold text-body-1">ON TIME DELIVERY</div>
                                    <div class="statistic-number">
                                        <span class="number text-white fw-semibold">99</span>
                                        <span class="prefix text-brand">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /section-statistic -->
    <!-- section-awards -->
    <div class="section-awards flat-spacing">
        <div class="container">
            <div class="heading-section center mb-48">
                <div class="heading-sub fw-semibold style-1 mb-0 effectFade fadeUp">Awards</div>
            </div>
            <div class="d-grid gap-16">
                <div class="awards-item effectFade fadeUp">
                    <div class="image">
                        <img src="{{ asset('modules/cms/assets/images/partner/partner-7.svg') }}" alt="">
                    </div>
                    <div class="title text-body-1 text-white">Innovation in AI</div>
                    <div class="text text-body-1 text-white">Underwriting Risk Copilot (Fintech)</div>
                    <div class="year text-body-1 text-neutral-400">/ 2025</div>
                </div>
                <div class="awards-item effectFade fadeUp" data-delay="0.1">
                    <div class="image">
                        <img src="{{ asset('modules/cms/assets/images/partner/partner-8.svg') }}" alt="">
                    </div>
                    <div class="title text-body-1 text-white">Best AI Product Design</div>
                    <div class="text text-body-1 text-white">Support Copilot (SaaS)</div>
                    <div class="year text-body-1 text-neutral-400">/ 2025</div>
                </div>
                <div class="awards-item effectFade fadeUp" data-delay="0.2">
                    <div class="image">
                        <img src="{{ asset('modules/cms/assets/images/partner/partner-9.svg') }}" alt="">
                    </div>
                    <div class="title text-body-1 text-white">Data & AI Excellence</div>
                    <div class="text text-body-1 text-white">Clinical Note Summarizer (Healthcare)</div>
                    <div class="year text-body-1 text-neutral-400">/ 2024</div>
                </div>
                <div class="awards-item effectFade fadeUp" data-delay="0.3">
                    <div class="image">
                        <img src="{{ asset('modules/cms/assets/images/partner/partner-10.svg') }}" alt="">
                    </div>
                    <div class="title text-body-1 text-white">Innovation in AI</div>
                    <div class="text text-body-1 text-white">Underwriting Risk Copilot (Fintech)</div>
                    <div class="year text-body-1 text-neutral-400">/ 2024</div>
                </div>
            </div>
        </div>
    </div>
    <!-- /section-awards -->
    <!-- section-testimonials -->
    <div class="section-testimonials flat-spacing pt-0">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-lg-5">
                    <div class="col-left">
                        <div class="heading-section mb-48">
                            <div class="heading-sub fw-semibold style-1 effectFade fadeUp">Testimonials</div>
                            <div class="heading-title text-white effectFade fadeRotateX">What Our <br> Clients Says</div>
                        </div>
                        <div class="swiper-testimonial_wrap effectFade fadeUp">
                            <div dir="ltr" class="swiper tf-swiper swiper-testimonial">
                                <div class="swiper-wrapper">
                                    <div class="swiper-slide">
                                        <div>
                                            <div class="top-icon d-flex gap-4">
                                                <i class="icon icon-star-solid"></i>
                                                <i class="icon icon-star-solid"></i>
                                                <i class="icon icon-star-solid"></i>
                                                <i class="icon icon-star-solid"></i>
                                                <i class="icon icon-star-solid"></i>
                                            </div>
                                            <div class="text-body-1 text-white desc">
                                                We shipped our first copilot in 7 weeks and cut support tickets by 31%. The eval dashboards made every decision obvious.
                                            </div>
                                            <div class="cite">
                                                <img class="line-left" src="{{ asset('modules/cms/assets/images/item/line-1.png') }}" alt="">
                                                <div class="name text-body-3 text-neutral-400 fw-semibold">Elena Ruiz</div>
                                                <div class="line"></div>
                                                <div class="sub text-body-3 text-neutral-400">Cantos SaaS’s VP Product</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div>
                                            <div class="top-icon">
                                                <svg width="23" height="20" viewBox="0 0 23 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12.9375 20V10.3597C12.9375 7.72182 13.824 5.51559 15.5969 3.74101C17.4177 1.91847 19.8854 0.671463 23 0V6.40288C21.8021 6.78657 21.0115 7.26619 20.6281 7.84173C20.2448 8.3693 20.0292 9.04077 19.9813 9.85612H23V20H12.9375ZM0 20V10.3597C0 7.72182 0.886459 5.51559 2.65938 3.74101C4.48021 1.91847 6.94792 0.671463 10.0625 0V6.40288C8.9125 6.78657 8.12187 7.26619 7.69062 7.84173C7.30729 8.3693 7.09167 9.04077 7.04375 9.85612H10.0625V20H0Z" fill="#FD3A25"/>
                                                </svg>
                                            </div>
                                            <div class="text-body-1 text-white desc">
                                                SSO/SAML and RBAC landed smoothly. Latency stayed &lt;300 ms on p95—huge win for our agents.
                                            </div>
                                            <div class="cite">
                                                <img class="line-left" src="{{ asset('modules/cms/assets/images/item/line-1.png') }}" alt="">
                                                <div class="name text-body-3 text-neutral-400 fw-semibold">Marcus Tan</div>
                                                <div class="line"></div>
                                                <div class="sub text-body-3 text-neutral-400">VectorPay’s CTO</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div>
                                            <div class="top-icon">
                                                <svg width="23" height="20" viewBox="0 0 23 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12.9375 20V10.3597C12.9375 7.72182 13.824 5.51559 15.5969 3.74101C17.4177 1.91847 19.8854 0.671463 23 0V6.40288C21.8021 6.78657 21.0115 7.26619 20.6281 7.84173C20.2448 8.3693 20.0292 9.04077 19.9813 9.85612H23V20H12.9375ZM0 20V10.3597C0 7.72182 0.886459 5.51559 2.65938 3.74101C4.48021 1.91847 6.94792 0.671463 10.0625 0V6.40288C8.9125 6.78657 8.12187 7.26619 7.69062 7.84173C7.30729 8.3693 7.09167 9.04077 7.04375 9.85612H10.0625V20H0Z" fill="#FD3A25"/>
                                                </svg>
                                            </div>
                                            <div class="text-body-1 text-white desc">
                                                The best partner for agentic work. Multi-step planning, tool use, and audit trails—done right the first time.
                                            </div>
                                            <div class="cite">
                                                <img class="line-left" src="{{ asset('modules/cms/assets/images/item/line-1.png') }}" alt="">
                                                <div class="name text-body-3 text-neutral-400 fw-semibold">David Kim</div>
                                                <div class="line"></div>
                                                <div class="sub text-body-3 text-neutral-400">Northway's Ecommerce Director</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="group-slider">
                                <div class="group-btn-slider">
                                    <div class="btn-slider nav-prev-swiper testimonials-prev">
                                        <i class="icon icon-angle-left-solid"></i>
                                    </div>
                                    <div class="btn-slider nav-next-swiper testimonials-next">
                                        <i class="icon icon-angle-right-solid"></i>
                                    </div>
                                </div>
                                <div class="testimonials-pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div dir="ltr" class="swiper sw-main-image effectFade fadeUp">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="testimonial-image">
                                    <img src="{{ asset('modules/cms/assets/images/section/tes-1.jpg') }}" alt="">
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial-image">
                                    <img src="{{ asset('modules/cms/assets/images/section/tes-2.jpg') }}" alt="">
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial-image">
                                    <img src="{{ asset('modules/cms/assets/images/section/tes-3.jpg') }}" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /section-testimonials -->
</div>

<!-- section-pricing -->
<div id="pricing" class="section-pricing flat-spacing">
    <div class="container">
        <div class="heading-section mb-80">
            <div class="heading-sub fw-semibold effectFade fadeUp">Pricing Plans</div>
            <div class="heading-title text-gradient-3 gap-8 effectFade fadeRotateX">
                From pilot to enterprise <br>
                <div class="h-8 d-none d-lg-block"></div>
                clear scope, transparent costs <br>
                <div class="h-8 d-none d-lg-block"></div>
                <input type="checkbox" id="pricingSwitch" class="tf-switch-check" checked>&nbsp;annually.
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 lg-mb-24">
                <div class="pricing-item h-100 effectFade fadeRotateX">
                    <div class="top d-flex gap-12 align-items-center">
                        <div class="d-flex gap-8 align-items-center">
                            <i class="icon icon-user-friends-solid fs-24"></i>
                            <div class="fw-semibold text">Starter Plan</div>
                        </div>
                        <div class="line"></div>
                        <div class="fw-semibold text-secondary">For startups</div>
                    </div>
                    <div class="heading">
                        <div class="d-flex gap-14 align-items-end">
                            <div class="price-number fw-bold" data-month="1000" data-year="9900">$9,900</div>
                            <h6 class="price-per">/ year</h6>
                        </div>
                        <a href="{{ route('cms.contact.us') }}" class="tf-btn">
                            Get Started
                        </a>
                    </div>
                    <div class="line"></div>
                    <div class="content">
                        <div>
                            <div class="title fw-semibold mb-4">What’s included</div>
                            <div class="text fw-semibold">
                                Prove value in two weeks with a clickable UX, tech spike, and a clear go/no-go roadmap.
                            </div>
                        </div>
                        <ul class="list-text type-check">
                            <li>
                                <i class="icon icon-check-solid"></i>
                                Discovery workshop
                            </li>
                            <li>
                                <i class="icon icon-check-solid"></i>
                                Opportunity brief
                            </li>
                            <li>
                                <i class="icon icon-check-solid"></i>
                                Clickable UX
                            </li>
                            <li>
                                <i class="icon icon-check-solid"></i>
                                1 data source & 1 integration
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="pricing-item h-100 style-black effectFade fadeRotateX" data-delay="0.1">
                    <div class="top d-flex gap-12 align-items-center">
                        <div class="d-flex gap-8 align-items-center">
                            <i class="icon icon-building fs-24"></i>
                            <div class="fw-semibold text">Enterprise Plan</div>
                        </div>
                        <div class="line"></div>
                        <div class="fw-semibold text-neutral-400">For organisations</div>
                    </div>
                    <div class="heading">
                        <div class="d-flex gap-14 align-items-end">
                            <div class="price-number fw-bold" data-month="1700" data-year="19900">$19,900</div>
                            <h6 class="price-per">/ year</h6>
                        </div>
                        <a href="{{ route('cms.contact.us') }}" class="tf-btn">
                            Get Started
                        </a>
                    </div>
                    <div class="line"></div>
                    <div class="content">
                        <div>
                            <div class="title fw-semibold mb-4">What’s included</div>
                            <div class="text fw-semibold">
                                Compliance-ready delivery for complex orgs—multi-env releases, canaries, and change management.
                            </div>
                        </div>
                        <ul class="list-text type-check">
                            <li>
                                <i class="icon icon-check-solid"></i>
                                Everything in Starter
                            </li>
                            <li>
                                <i class="icon icon-check-solid"></i>
                                CI/CD, tracing, alerts, guardrails
                            </li>
                            <li>
                                <i class="icon icon-check-solid"></i>
                                Full eval dashboard
                            </li>
                            <li>
                                <i class="icon icon-check-solid"></i>
                                3 data source & 3 integration
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /section-pricing -->

<!-- section-faqs -->
<div class="section-faqs flat-spacing pt-0">
    <div class="container">
        <div class="heading-section center mb-64">
            <div class="heading-sub fw-semibold effectFade fadeUp">FAQs</div>
            <div class="heading-title text-gradient-3 effectFade fadeRotateX">
                Frequently Asked <br>Questions
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion-asked" id="accordion-asked">
                    <div class="accordion-asked-item effectFade fadeRotateX">
                        <div class="accordion-asked-title" id="asked1">
                            <button class="accordion-button text-body-1 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                What’s the typical timeline from idea to v1?
                                <span class="right-icon"></span>
                            </button>
                        </div>
                        <div id="collapse1" role="region" class="accordion-collapse collapse show" aria-labelledby="asked1" data-bs-parent="#accordion-asked">
                            <div class="accordion-body">
                                A clear problem statement, success metrics, access to sample data, and a stakeholder who can make decisions. We’ll run a kickoff workshop to align scope.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-asked-item effectFade fadeRotateX" data-delay="0.1">
                        <div class="accordion-asked-title" id="asked2">
                            <button class="accordion-button text-body-1 fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                What do we need to start?
                                <span class="right-icon"></span>
                            </button>
                        </div>
                        <div id="collapse2" role="region" class="accordion-collapse collapse" aria-labelledby="asked2" data-bs-parent="#accordion-asked">
                            <div class="accordion-body">
                                A clear problem statement, success metrics, access to sample data, and a stakeholder who can make decisions. We’ll run a kickoff workshop to align scope.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-asked-item effectFade fadeRotateX" data-delay="0.2">
                        <div class="accordion-asked-title" id="asked3">
                            <button class="accordion-button text-body-1 fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                Which models/stack do you use?
                                <span class="right-icon"></span>
                            </button>
                        </div>
                        <div id="collapse3" role="region" class="accordion-collapse collapse" aria-labelledby="asked3" data-bs-parent="#accordion-asked">
                            <div class="accordion-body">
                                A clear problem statement, success metrics, access to sample data, and a stakeholder who can make decisions. We’ll run a kickoff workshop to align scope.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-asked-item effectFade fadeRotateX" data-delay="0.3">
                        <div class="accordion-asked-title" id="asked4">
                            <button class="accordion-button text-body-1 fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                Are model/API costs included in pricing?
                                <span class="right-icon"></span>
                            </button>
                        </div>
                        <div id="collapse4" role="region" class="accordion-collapse collapse" aria-labelledby="asked4" data-bs-parent="#accordion-asked">
                            <div class="accordion-body">
                                A clear problem statement, success metrics, access to sample data, and a stakeholder who can make decisions. We’ll run a kickoff workshop to align scope.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /section-faqs -->
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
                            <div class="heading-sub fw-semibold effectFade fadeUp">Contact</div>
                            <div class="heading-title text-gradient-3 effectFade fadeRotateX">
                                Let’s Build <br> Intelligent Things
                            </div>
                        </div>
                        <div>
                            <div class="contact-item mb-20 effectFade fadeRotateX">
                                <i class="icon icon-envelope-solid"></i>
                                <div class="content">
                                    <div class="title fw-semibold mb-2">E-mail address</div>
                                    <div class="text">hello@youraiagency.com</div>
                                </div>
                            </div>
                            <div class="contact-item effectFade fadeRotateX" data-delay="0.1">
                                <i class="icon icon-headset-solid"></i>
                                <div class="content">
                                    <div class="title fw-semibold mb-2">Phone number</div>
                                    <div class="text">+1 (647) 555 0172</div>
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