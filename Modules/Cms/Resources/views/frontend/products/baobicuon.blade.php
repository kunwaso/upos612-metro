@extends('cms::frontend.layouts.app')
@section('title', 'Bao bì dạng cuộn')
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')
@section('content')
<!-- Hero Banner -->
<div class="section-hero v1">
    <div class="hero-roller"></div>
    <div class="container">
        <div class="content-wrap text-center">
            <div class="title text-display-2 effectFade fadeRotateX">
                <span class="title1 fw-semibold text-gradient-4"
                    > Bao bì dạng cuộn</span
                >
                <br />
                <div class="title2 d-flex gap-20 justify-content-center flex-wrap">
                    <span class="fw-semibold text-gradient-1"></span>
                </div>
            </div>
            <p class="text effectFade fadeUp">
                các loại cuộn bao bì được thiết kế với độ bền và chất lượng vượt trội để vận chuyển hoàn hảo. 
                <br /> 
                
            </p>
        </div>
    </div>
</div>
<!-- /Hero Banner -->
<!-- section-services -->
<div id="services" class="section-services flat-spacing">
    <div class="container">
        <div class="top">
            <div class="heading-section center mb-48">
                <div class="heading-title text-gradient-3 effectFade fadeRotateX">
                    Bao bì dạng cuộn
                </div>
            </div>
            <p class="text text-center effectFade fadeUp">
                Bao bì dạng cuộn là một loại bao bì được sử dụng để bảo vệ và giữ cho sản phẩm của bạn an toàn và sạch sẽ.
            </p>
        </div>
        <div class="accordion-faq_list gap-32" >
            <div  class="accordion-faq_item style-1 effectFade fadeRotateX" >
                <div class="accordion-action ">
                    <div class="accordion-title">
                        Màng PE
                        <i class="icon icon-arrow-top-right"></i>
                    </div>
                </div>
                <div class="accordion-content">
                    <div class="image">
                        <img src="assets/images/section/service-8.jpg" alt="" />
                    </div>
                    <div class="content">
                        <div class="text-body-3 text-neutral-300 text">
                            Màng PE là một loại màng được sử dụng để bảo vệ và giữ cho sản phẩm của bạn an toàn và sạch sẽ.
                        </div>

                    
                        <div class="list-tags">
                            <a href="#" class="tags-item fw-semibold"
                                >Chủng loại: Màng PE quấn taynhẹ, nhỏ gọnvà màng PE quấn máy khổ rộng 50cm - 1m5, nặng 15kg - 25kg.</a
                            >
                            <a href="#" class="tags-item fw-semibold"
                                >Ứng dụng: Quấn pallet hàng hóa, bao bọc hàng hóa, chống bụi bẩn, chống trầy xước trong quá trình vận chuyển.</a
                            >
                            <a href="#" class="tags-item fw-semibold"
                                >Chất liệu: Nhựa Polyetylen, độ dãn cao, bám dính tốt</a
                            >
                        </div>
                        <div class="text-body-1 num">01</div>
                    </div>
                </div>
            
            </div>
        </div>

        <div class="accordion-faq_list pt-30">
            <div  class="accordion-faq_item style-1 effectFade fadeRotateX" >
                <div class="accordion-action ">
                    <div class="accordion-title">
                        Data Engineering & Pipelines
                        <i class="icon icon-arrow-top-right"></i>
                    </div>
                </div>
                <div class="accordion-content">
                    <div class="image">
                        <img src="assets/images/section/service-8.jpg" alt="" />
                    </div>
                    <div class="content">
                        <div class="text-body-3 text-neutral-300 text">
                            Reliable data flows from ingestion to features, built
                            for scale and cost control. Our robust data engineering
                            ensures clean, consistent, and efficient
                            pipelines—enabling seamless integration, real-time
                            analytics, and optimized performance that power scalable
                            AI systems and sustainable business growth.
                        </div>
                        <div class="list-tags">
                            <a href="#" class="tags-item fw-semibold"
                                >Data cleaning & chunking</a
                            >
                            <a href="#" class="tags-item fw-semibold"
                                >Hybrid search</a
                            >
                            <a href="#" class="tags-item fw-semibold"
                                >Freshness, citations, and re-ranking</a
                            >
                        </div>
                        <div class="text-body-1 num">04</div>
                    </div>
                </div>
            
            </div>
        </div>
        <div class="accordion-faq_list pt-30">
            <div  class="accordion-faq_item style-1 effectFade fadeRotateX" >
                <div class="accordion-action ">
                    <div class="accordion-title">
                        Data Engineering & Pipelines
                        <i class="icon icon-arrow-top-right"></i>
                    </div>
                </div>
                <div class="accordion-content">
                    <div class="image">
                        <img src="assets/images/section/service-8.jpg" alt="" />
                    </div>
                    <div class="content">
                        <div class="text-body-3 text-neutral-300 text">
                            Reliable data flows from ingestion to features, built
                            for scale and cost control. Our robust data engineering
                            ensures clean, consistent, and efficient
                            pipelines—enabling seamless integration, real-time
                            analytics, and optimized performance that power scalable
                            AI systems and sustainable business growth.
                        </div>
                        <div class="list-tags">
                            <a href="#" class="tags-item fw-semibold"
                                >Data cleaning & chunking</a
                            >
                            <a href="#" class="tags-item fw-semibold"
                                >Hybrid search</a
                            >
                            <a href="#" class="tags-item fw-semibold"
                                >Freshness, citations, and re-ranking</a
                            >
                        </div>
                        <div class="text-body-1 num">04</div>
                    </div>
                </div>
            
            </div>
        </div>

</div>
<!-- /section-services -->
<!-- section-pricing -->
<div id="pricing" class="section-pricing flat-spacing pt-0">
    <div class="container">
        <div class="heading-section mb-80">
            <div class="heading-sub fw-semibold effectFade fadeUp">Pricing Plans</div>
            <div class="heading-title text-gradient-3 gap-8 d-grid effectFade fadeRotateX">
                <span>From pilot to enterprise</span> 
                <span>clear scope, transparent costs</span>
                <div class="d-flex align-items-center gap-24 flex-wrap">
                    <input type="checkbox" id="pricingSwitch" class="tf-switch-check" checked> 
                    annually.
                </div>
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
                        <a href="contact.html" class="tf-btn">
                            Get Started
                        </a>
                    </div>
                    <div class="line"></div>
                    <img src="{{ asset('modules/cms/assets/images/products/rolls/roll-packaging.png') }}" alt="Bao bì cuộn" class="img-fluid">
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
                        <a href="contact.html" class="tf-btn">
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
            
@endsection
@section('javascript')
@endsection