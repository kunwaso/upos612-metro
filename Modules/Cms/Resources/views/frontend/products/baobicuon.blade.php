@extends('cms::frontend.layouts.app')
@section('title', 'Bao bì dạng cuộn')
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')
@section('content')



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