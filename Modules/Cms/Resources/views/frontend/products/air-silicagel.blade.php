@extends('cms::frontend.layouts.app')
@section('title', 'Túi Silicagel')
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')
@section('content')

            <!-- Hero Banner -->
            <div class="section-hero v1">
                <div class="hero-image"></div>
                <div class="container">
                    <div class="content-wrap text-center">
                        <div class="title text-display-2 effectFade fadeRotateX">
                            <span class="title1 fw-semibold text-gradient-1"
                                >Build Smarter with</span
                            >
                            <br />
                            <div class="title2 d-flex gap-20 justify-content-center flex-wrap">
                                <span class="fw-semibold text-gradient-1">Full-Stack AI</span>
                                <div class="title-icon">
                                    <div class="box"></div>
                                    <div class="title-icon-wrap">
                                        <img
                                            src="assets/images/item/item-13.svg"
                                            alt=""
                                            class="img-1 img-transform-3"
                                        />
                                        <img
                                            src="assets/images/item/item-14.svg"
                                            alt=""
                                            class="img-2 img-transform-3"
                                        />
                                        <img
                                            src="assets/images/item/item-15.svg"
                                            alt=""
                                            class="img-3 img-transform-3"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text effectFade fadeUp">
                            Unlock growth with our full-stack AI services, delivering smart,
                            efficient solutions from <br />
                            strategy to deployment for innovative business success.
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
                            <div class="heading-sub fw-semibold effectFade fadeUp">Services</div>
                            <div class="heading-title text-gradient-3 effectFade fadeRotateX">
                                End-to-End AI Services
                            </div>
                        </div>
                        <p class="text text-center effectFade fadeUp">
                            We turn ambiguous AI ideas into production features your users
                            trust—combining strategy, <br />
                            design, engineering, and rigorous evaluation.
                        </p>
                    </div>
                    <div class="accordion-faq_list gap-32" id="accordion-services">
                        <div
                            class="accordion-faq_item style-1 effectFade fadeRotateX"
                            role="presentation"
                        >
                            <div
                                class="accordion-action"
                                data-bs-target="#faq-1"
                                role="button"
                                data-bs-toggle="collapse"
                                aria-controls="faq-1"
                                aria-expanded="true"
                            >
                                <div class="accordion-title">
                                    AI Strategy & Mapping
                                    <i class="icon icon-arrow-top-right"></i>
                                </div>
                            </div>
                            <div
                                id="faq-1"
                                class="collapse show"
                                data-bs-parent="#accordion-services"
                            >
                                <div class="accordion-content">
                                    <div class="image">
                                        <img src="assets/images/section/service-5.jpg" alt="" />
                                    </div>
                                    <div class="content">
                                        <div class="text-body-3 text-neutral-300 text">
                                            Identify high-ROI use cases and define a realistic,
                                            measurable AI roadmap. Our AI Strategy & Mapping process
                                            aligns technology with business goals through
                                            stakeholder discovery, KPI modeling, and data readiness
                                            assessment to ensure sustainable growth and measurable
                                            transformation outcomes.
                                        </div>
                                        <div class="list-tags">
                                            <a href="#" class="tags-item fw-semibold"
                                                >Stakeholder discovery</a
                                            >
                                            <a href="#" class="tags-item fw-semibold"
                                                >Value model & KPI definition</a
                                            >
                                            <a href="#" class="tags-item fw-semibold"
                                                >Data readiness assessment</a
                                            >
                                        </div>
                                        <div class="text-body-1 num">01</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="accordion-faq_item style-1 effectFade fadeRotateX"
                            role="presentation"
                        >
                            <div
                                class="accordion-action collapsed"
                                data-bs-target="#faq-2"
                                role="button"
                                data-bs-toggle="collapse"
                                aria-controls="faq-2"
                                aria-expanded="false"
                            >
                                <div class="accordion-title">
                                    AI UX & Product Design
                                    <i class="icon icon-arrow-top-right"></i>
                                </div>
                            </div>
                            <div id="faq-2" class="collapse" data-bs-parent="#accordion-services">
                                <div class="accordion-content">
                                    <div class="image">
                                        <img src="assets/images/section/service-6.jpg" alt="" />
                                    </div>
                                    <div class="content">
                                        <div class="text-body-3 text-neutral-300 text">
                                            Human-centered flows, prompts, and interfaces that build
                                            trust and adoption. We design intuitive AI experiences
                                            focused on transparency, usability, and
                                            engagement—helping users understand, trust, and
                                            confidently interact with intelligent systems that
                                            seamlessly integrate into their workflows for lasting
                                            impact and satisfaction.
                                        </div>
                                        <div class="list-tags">
                                            <a href="#" class="tags-item fw-semibold"
                                                >Prototype flows</a
                                            >
                                            <a href="#" class="tags-item fw-semibold"
                                                >Prompt UX patterns</a
                                            >
                                            <a href="#" class="tags-item fw-semibold"
                                                >Usability testing with real users</a
                                            >
                                        </div>
                                        <div class="text-body-1 num">02</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="accordion-faq_item style-1 effectFade fadeRotateX"
                            role="presentation"
                        >
                            <div
                                class="accordion-action collapsed"
                                data-bs-target="#faq-3"
                                role="button"
                                data-bs-toggle="collapse"
                                aria-controls="faq-3"
                                aria-expanded="false"
                            >
                                <div class="accordion-title">
                                    LLM / Agent Development
                                    <i class="icon icon-arrow-top-right"></i>
                                </div>
                            </div>
                            <div id="faq-3" class="collapse" data-bs-parent="#accordion-services">
                                <div class="accordion-content">
                                    <div class="image">
                                        <img src="assets/images/section/service-7.jpg" alt="" />
                                    </div>
                                    <div class="content">
                                        <div class="text-body-3 text-neutral-300 text">
                                            Domain-specific copilots and agents that plan, execute,
                                            and report. These intelligent systems are tailored to
                                            your industry, automating complex tasks, enhancing
                                            decision-making, and delivering actionable
                                            insights—empowering teams to work smarter, faster, and
                                            with greater accuracy across every stage of operations.
                                        </div>
                                        <div class="list-tags">
                                            <a href="#" class="tags-item fw-semibold"
                                                >Multi-step planning</a
                                            >
                                            <a href="#" class="tags-item fw-semibold"
                                                >Function calling & toolchains</a
                                            >
                                            <a href="#" class="tags-item fw-semibold"
                                                >Guardrails and audit trails</a
                                            >
                                        </div>
                                        <div class="text-body-1 num">03</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="accordion-faq_item style-1 effectFade fadeRotateX"
                            role="presentation"
                        >
                            <div
                                class="accordion-action collapsed"
                                data-bs-target="#faq-4"
                                role="button"
                                data-bs-toggle="collapse"
                                aria-controls="faq-4"
                                aria-expanded="false"
                            >
                                <div class="accordion-title">
                                    Data Engineering & Pipelines
                                    <i class="icon icon-arrow-top-right"></i>
                                </div>
                            </div>
                            <div id="faq-4" class="collapse" data-bs-parent="#accordion-services">
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
                </div>
            </div>
            <!-- /section-services -->
            

@endsection
@section('javascript')
@endsection