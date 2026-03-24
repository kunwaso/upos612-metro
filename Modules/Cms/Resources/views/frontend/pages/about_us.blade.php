@extends('cms::frontend.layouts.app')
@section('title', 'About Us')
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')

        <!-- Hero Banner -->
        <div class="section-hero v1">
            <div class="hero-image">
            </div>
            <div class="container">
                <div class="content-wrap text-center">
                    <div class="title text-display-2 effectFade fadeRotateX">
                        <span class="title1 fw-semibold text-gradient-1">Aigocy Empowering</span>
                        <br>
                        <div class="title2 d-flex gap-20 justify-content-center flex-wrap">
                            <span class="fw-semibold text-gradient-1">Industries</span>
                            <div class="title-icon">
                                <div class="box"></div>
                                <div class="title-icon-wrap">
                                    <img src="assets/images/item/item-10.svg" alt="" class="img-1 img-transform-3">
                                    <img src="assets/images/item/item-11.svg" alt="" class="img-2 img-transform-3">
                                    <img src="assets/images/item/item-12.svg" alt="" class="img-3 img-transform-3">
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text effectFade fadeUp">
                        We build human-centered AI solutions that transform workflows, enhance decision-making, <br> and accelerate innovation across diverse global industries.
                    </p>
                </div>
            </div>
        </div>
        <!-- /Hero Banner -->
        <!-- Select Work -->
        <div class="section-about-us flat-spacing" id="about">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="heading-section">
                            <div class="heading-sub fw-semibold effectFade fadeUp">About Us</div>
                            <div class="heading-title text-gradient-2 effectFade fadeRotateX">Human-Centered AI, <br> Built for Production</div>
                        </div>
                    </div>
                    <div class="col-xxl-7 col-lg-6 lg-mb-24">
                        <div class="col-left">
                            <div class="position-relative z-5">
                                <div class="sub text-white">
                                    <span class="dot"></span>
                                    Available for worldwide project
                                </div>
                                <h5 class="title fw-semibold text-white">Based in <span class="text-brand">Montréal, Canada</span></h5>
                                <a href="contact.html" class="tf-btn">
                                    Start a Project
                                </a>
                            </div>
                            <img class="effectFade fadeRotateX" src="assets/images/item/earth.png" alt="">
                        </div>
                    </div>
                    <div class="col-xxl-5 col-lg-6">
                        <div class="mission-box mb-24">
                            <h4 class="title fw-semibold effectFade fadeUp">Our Mission</h4>
                            <div class="line"></div>
                            <p class="text effectFade fadeUp">
                                Based in Montréal, Canada, we build human-centered AI for production. Our mission is to create intelligent systems that empower people and organizations. By uniting research, design, and engineering, we deliver scalable and reliable AI solutions—technology that’s practical, trustworthy, and designed to make a meaningful impact in the real world.
                            </p>
                        </div>
                        <div class="box-quotes effectFade fadeRotateX">
                            <div class="image ">
                                <img src="assets/images/section/quotes-1.jpg" alt="">
                            </div>
                            <div class="content">
                                <div class="icon mb-8">
                                    <svg width="23" height="20" viewBox="0 0 23 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12.9375 20V10.3597C12.9375 7.72182 13.824 5.51559 15.5969 3.74101C17.4177 1.91847 19.8854 0.671463 23 0V6.40288C21.8021 6.78657 21.0115 7.26619 20.6281 7.84173C20.2448 8.3693 20.0292 9.04077 19.9813 9.85612H23V20H12.9375ZM0 20V10.3597C0 7.72182 0.886459 5.51559 2.65938 3.74101C4.48021 1.91847 6.94792 0.671463 10.0625 0V6.40288C8.9125 6.78657 8.12187 7.26619 7.69062 7.84173C7.30729 8.3693 7.09167 9.04077 7.04375 9.85612H10.0625V20H0Z" fill="#E4E4E7"/>
                                    </svg>
                                </div>
                                <div class="text-body-1 fw-semibold desc">Good AI feels obvious—because the hard work is hidden.</div>
                                <div class="cite">
                                    <div class="name text-body-3 fw-semibold">Ava Collins</div>
                                    <div class="line"></div>
                                    <div class="sub text-body-3">Aigocy’s Design Lead</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Select Work -->
        <!-- section-partner -->
        <div class="section-partner">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="partner-wrap">
                            <p class="text-secondary text fw-semibold">Trusted by 100+ <br> top-tier brands</p>
                            <div class="infiniteSlide_tech_main d-grid">
                                <div class="infiniteSlide infiniteSlide_partner" data-clone="5">
                                    <img src="assets/images/partner/partner-1.svg" alt="">
                                    <img src="assets/images/partner/partner-2.svg" alt="">
                                    <img src="assets/images/partner/partner-3.svg" alt="">
                                    <img src="assets/images/partner/partner-4.svg" alt="">
                                    <img src="assets/images/partner/partner-5.svg" alt="">
                                    <img src="assets/images/partner/partner-6.svg" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /section-partner -->
        <div class="box-white">
            <!-- section-features -->
            <div class="section-features flat-spacing">
                <div class="container">
                    <div class="heading-section center mb-64">
                        <div class="heading-sub fw-semibold effectFade fadeUp">Core Values</div>
                        <div class="heading-title text-gradient-3 effectFade fadeRotateX">Guided by Principles</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-24">
                            <div class="features-item style-2 h-100 effectFade fadeRotateX">
                                <div class="icon">
                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <mask id="mask0_2230_475" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="1" y="1" width="38" height="38">
                                        <path d="M1.66602 1.66683H38.3327V38.3335H1.66602V1.66683Z" fill="white"/>
                                        </mask>
                                        <g mask="url(#mask0_2230_475)">
                                        <path d="M30.5527 3.23727C32.7492 2.67731 35.1046 2.36572 37.6168 2.38362C37.6347 4.89586 37.3232 7.25133 36.7632 9.44775" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M16.3852 29.6918H16.3844L12.1577 30.8813L9.11914 27.8427L10.3087 23.616V23.6153" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9.11888 33.9197C8.27956 34.7583 2.38281 37.6172 2.38281 37.6172C2.38281 37.6172 5.24167 31.7204 6.08027 30.8811C6.9196 30.0418 8.27956 30.0418 9.11888 30.8811C9.9582 31.7204 9.9582 33.0804 9.11888 33.9197Z" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21.3721 18.6279C19.6963 16.9522 19.6963 14.2272 21.3721 12.5514C23.0479 10.8757 25.7728 10.8757 27.4486 12.5514C29.1244 14.2272 29.1244 16.9522 27.4486 18.6279C25.7728 20.3037 23.0479 20.3037 21.3721 18.6279Z" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M36.7631 9.44775H36.7624C35.1905 9.39189 33.6365 8.76376 32.4362 7.56422C31.2367 6.36396 30.6086 4.80992 30.5527 3.23805V3.23727" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M17.9033 22.0962L13.3457 26.6538" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M23.4945 27.9998C24.9118 30.4626 24.5673 33.6631 22.4618 35.7686L17.9043 31.211" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M8.78802 22.0962L4.23047 17.5386C6.33594 15.4332 9.53568 15.0887 11.9992 16.5053" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M33.1197 17.2099C32.8403 16.9305 32.8403 16.4766 33.1197 16.1971C33.3991 15.9177 33.8531 15.9177 34.1325 16.1971C34.4118 16.4766 34.4118 16.9305 34.1325 17.2099C33.8531 17.4893 33.3991 17.4893 33.1197 17.2099Z" fill="white"/>
                                        <path d="M36.7623 9.44748C36.3637 11.0125 35.8394 12.497 35.2188 13.8999" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M32.0012 19.4878C29.3782 23.1357 26.2324 25.972 23.4729 27.9709C21.1719 29.6388 19.1394 30.7238 17.9041 31.2114L8.78906 22.0963C9.27812 20.8582 10.3632 18.8257 12.0296 16.5262C15.5939 11.6069 21.8171 5.4624 30.5527 3.23812" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                    </svg>
                                </div>
                                <h4 class="title fw-semibold">Innovation</h4>
                                <p class="text-secondary">
                                    Innovation drives everything we do. We leverage emerging technologies, challenge conventions, and explore new possibilities to create solutions. Through insight, experimentation, and strategy, we craft transformative experiences that fuel growth, redefine standards, and create meaningful impact—ensuring every project shapes the future with purpose.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-24">
                            <div class="features-item style-2 h-100 effectFade fadeRotateX" data-delay="0.1">
                                <div class="icon">
                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.1659 25.2949C22.1659 26.6188 21.1569 27.6919 19.9124 27.6919H14.9512" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M31.426 28.1045L33.9418 26.3729C35.1353 25.6399 35.5442 24.0166 34.8551 22.747C34.1661 21.4775 32.64 21.0425 31.4464 21.7755L24.8808 25.8076C24.2426 26.1996 23.5185 26.4059 22.7815 26.4059H21.9099C22.0736 26.0737 22.1668 25.696 22.1668 25.2949C22.1668 23.971 21.1578 22.8978 19.9133 22.8978H12.0913C10.3674 22.8978 8.9069 24.0944 8.40175 25.749L3.84559 31.8169C3.03922 32.8908 3.20396 34.4568 4.21358 35.3145L7.12661 37.7894C8.13623 38.6472 9.60835 38.4719 10.4148 37.398L13.2396 33.636C13.9635 32.6719 15.0605 32.7609 16.2204 32.7609L23.9412 32.7608C24.3725 32.7608 24.7962 32.64 25.1696 32.4106L29.2866 29.577" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22.1484 25.5974L22.1492 25.5967L25.9509 20.7081C26.7634 19.6628 28.2691 19.4745 29.3144 20.287C29.9223 20.7592 30.2404 21.4665 30.2404 22.1804C30.2404 22.2976 30.2315 22.4155 30.2137 22.5318" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M14.101 4.15918C13.1913 5.29274 12.6445 6.7282 12.6445 8.28346C12.6445 10.3529 13.5948 12.201 15.0836 13.4138C15.8951 14.0756 16.3658 15.0664 16.3658 16.1133V16.7603" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22.1568 16.7604V16.1134C22.1568 15.0621 22.6356 14.072 23.4493 13.4059C24.9322 12.1922 25.878 10.3486 25.878 8.28287C25.878 4.64499 22.9417 1.69248 19.309 1.66666C18.14 1.65862 17.0364 1.95985 16.0762 2.49302" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21.7849 20.3893H16.7366C16.0051 20.3893 15.4121 19.7963 15.4121 19.0648V18.0315C15.4121 17.3 16.0051 16.707 16.7366 16.707H21.7849C22.5164 16.707 23.1094 17.3 23.1094 18.0315V19.0648C23.1094 19.7963 22.5164 20.3893 21.7849 20.3893Z" stroke="white" stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <h4 class="title fw-semibold">Creativity</h4>
                                <p class="text-secondary">
                                    Creativity is the core of our agency. We transform ideas into compelling experiences that inspire, engage, and resonate. By blending imagination with strategy, we craft innovative solutions that stand out in crowded markets. Every project is an opportunity to explore new perspectives, push boundaries, and deliver work that is both original and impactful, leaving a lasting impression.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 md-mb-24">
                            <div class="features-item style-2 h-100 effectFade fadeRotateX">
                                <div class="icon">
                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <mask id="mask0_2230_487" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="1" y="1" width="38" height="38">
                                        <path d="M1.66602 1.66683H38.3327V38.3335H1.66602V1.66683Z" fill="white"/>
                                        </mask>
                                        <g mask="url(#mask0_2230_487)">
                                        <path d="M34.504 20.8641L31.9195 22.3563C31.8423 22.4008 31.7435 22.3744 31.6989 22.2971L27.0451 14.2366C27.0005 14.1593 27.027 14.0606 27.1042 14.016L29.6887 12.5238" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22.5197 17.1508L20.8432 16.1828C20.6334 16.0616 20.3748 16.0616 20.1649 16.1828L16.8175 18.1149C15.9908 18.5921 14.9337 18.3089 14.4565 17.4822C13.9793 16.6555 14.2624 15.5984 15.0891 15.1212L18.6185 13.0836C19.523 12.5613 20.6374 12.5613 21.542 13.0836L23.9111 14.4513C24.8019 14.9657 25.8996 14.9657 26.7906 14.4515L27.0758 14.2873L31.6713 22.2471" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M31.1161 22.1133L24.7578 18.4423" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10.8156 24.9898L12.3523 24.1026C13.1789 23.6253 13.4622 22.5683 12.9849 21.7415C12.5076 20.9149 11.4506 20.6316 10.6239 21.1089L9.08717 21.9961C8.26045 22.4734 7.97721 23.5305 8.45453 24.3572C8.93184 25.1838 9.98894 25.4671 10.8156 24.9898Z" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12.8117 27.8292L13.7246 27.3022C14.5512 26.8248 14.8344 25.7677 14.3572 24.9411C13.8799 24.1144 12.8228 23.8311 11.9961 24.3085L11.0833 24.8355C10.2565 25.3128 9.97331 26.3699 10.4506 27.1965C10.9279 28.0232 11.985 28.3065 12.8117 27.8292Z" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M15.4933 30.2725L16.3171 29.7969C17.1438 29.3196 17.4271 28.2625 16.9497 27.4359C16.4724 26.6092 15.4154 26.3259 14.5887 26.8032L13.7649 27.2788C12.9382 27.7561 12.6549 28.8133 13.1323 29.6399C13.6095 30.4665 14.6666 30.7499 15.4933 30.2725Z" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M18.8879 32.109L19.3552 31.8391C20.1819 31.3618 20.4651 30.3048 19.9879 29.4781C19.5105 28.6514 18.4535 28.3682 17.6268 28.8454L17.1594 29.1153C16.3327 29.5926 16.0495 30.6497 16.5268 31.4763C17.0041 32.303 18.0612 32.5863 18.8879 32.109Z" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22.1895 28.9348L23.1151 29.4692" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M23.916 25.9411L26.4926 27.4287" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M25.6445 22.9471L29.4784 25.1606" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M5.49414 20.8641L8.07857 22.3563C8.15584 22.4008 8.2546 22.3744 8.29921 22.2971L12.953 14.2366C12.9976 14.1593 12.9711 14.0606 12.8939 14.016L10.3094 12.5238" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M19.7559 31.5213L21.353 32.4433C22.1922 32.9279 23.2957 32.6493 23.7642 31.8011C24.2184 30.9788 23.9321 29.9408 23.1152 29.4691L24.7323 30.4028C25.5715 30.8873 26.675 30.6087 27.1435 29.7605C27.5977 28.9383 27.3114 27.9002 26.4944 27.4285L27.8044 28.1848C28.6771 28.6887 29.7598 28.3635 30.2105 27.4622L30.2132 27.4567C30.6129 26.6574 30.3067 25.6379 29.5328 25.1911L29.4802 25.1608C30.3069 25.638 31.3639 25.3548 31.8413 24.5281C32.2671 23.6763 31.9408 22.5898 31.1161 22.1136" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M5.76495 9.29969L2.35975 7.33365C2.29014 7.29348 2.20312 7.34375 2.20312 7.4241V19.5045C2.20312 19.5418 2.22303 19.5762 2.25533 19.5949L5.09385 21.2337C5.17112 21.2783 5.26988 21.2519 5.31442 21.1746L10.4884 12.2132C10.5329 12.1359 10.5064 12.0372 10.4292 11.9926L7.98121 10.5792" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M33.6072 19.3099L34.6838 21.1746C34.7284 21.2518 34.8272 21.2783 34.9044 21.2337L37.7429 19.5949C37.7752 19.5763 37.7951 19.5417 37.7951 19.5044V7.42412C37.7951 7.34377 37.7081 7.29349 37.6385 7.33367L29.569 11.9926C29.4918 12.0372 29.4653 12.1359 29.51 12.2132L32.3293 17.0964" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M15.2356 15.0366C14.5425 15.0193 13.8608 14.8278 13.2543 14.4777L12.9238 14.2873" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                    </svg>
                                </div>
                                <h4 class="title fw-semibold">Collaboration</h4>
                                <p class="text-secondary">
                                    At the heart of our work lies collaboration—bringing together diverse talents, perspectives, and expertise to achieve results. We foster open communication, encourage co-creation, and build strong partnerships with clients, ensuring every project reflects shared vision and purpose. By working together, we transform ideas into impactful solutions that drive innovation and lasting value.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="features-item style-2 h-100 effectFade fadeRotateX" data-delay="0.1">
                                <div class="icon">
                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <mask id="mask0_2230_497" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="1" y="1" width="38" height="38">
                                        <path d="M38.3327 1.6665H1.66602V38.3332H38.3327V1.6665Z" fill="white"/>
                                        </mask>
                                        <g mask="url(#mask0_2230_497)">
                                        <path d="M10.3677 37.4541L10.3904 35.043C10.3943 34.5564 10.2167 34.0866 9.89494 33.7234L3.62982 26.6579C3.00605 25.9552 2.65664 25.0502 2.64483 24.1106L2.53332 14.7592C2.52244 13.8552 3.24782 13.115 4.15189 13.1081H4.1637C5.01849 13.1081 5.73106 13.7694 5.79121 14.625L6.28371 21.5968C6.32224 22.1377 6.54231 22.6489 6.9094 23.0486L11.0348 27.5373C11.4829 28.0238 12.2301 28.085 12.7502 27.6754C13.0699 27.4248 13.2348 27.0527 13.2348 26.6786C13.2348 26.3756 13.1272 26.0717 12.9061 25.8278L9.34029 21.8968C8.86255 21.3698 8.86849 20.5645 9.35504 20.0443C9.62453 19.7562 9.98869 19.612 10.3529 19.612C10.7082 19.612 11.0625 19.7483 11.331 20.0236L17.2526 26.0894C17.2971 26.1348 17.3395 26.1802 17.3819 26.2256C18.4172 27.3655 18.8278 28.9417 18.5514 30.4566L17.2743 37.4541H10.3677Z" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M34.8372 28.5497L36.5144 26.6583C37.1381 25.9557 37.4875 25.0507 37.4983 24.1111L37.6098 14.7597C37.6207 13.8557 36.8953 13.1154 35.9912 13.1086H35.9794C35.1247 13.1086 34.4131 13.7699 34.352 14.6255L33.8604 21.5973C33.8219 22.1382 33.6009 22.6494 33.2347 23.0491L29.1083 27.5378C28.6602 28.0243 27.9131 28.0855 27.393 27.6759C27.0732 27.4252 26.9084 27.0531 26.9084 26.6791C26.9084 26.3761 27.016 26.0721 27.237 25.8283L30.8029 21.8973C31.2805 21.3703 31.2747 20.5649 30.7891 20.0448C30.5186 19.7566 30.1544 19.6125 29.7902 19.6125C29.436 19.6125 29.0806 19.7487 28.8122 20.0241L22.8905 26.0899C22.8471 26.1353 22.8037 26.1807 22.7622 26.2261C21.7259 27.366 21.3154 28.9421 21.5917 30.4571L22.8698 37.4546H29.7754L29.7528 35.0435C29.7498 34.5569 29.9265 34.0871 30.2492 33.7239L33.4958 30.0626" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M11.8738 10.7766C10.9812 9.90656 11.4737 8.39069 12.7072 8.21144L16.0583 7.72454C16.5481 7.65335 16.9716 7.34569 17.1906 6.90183L18.6892 3.8653C19.2408 2.74754 20.8348 2.74754 21.3863 3.8653L22.885 6.90183C23.104 7.34569 23.5275 7.65335 24.0173 7.72454L27.3683 8.21144C28.6019 8.39069 29.0944 9.90656 28.2018 10.7766L25.777 13.1402C25.4225 13.4857 25.2608 13.9835 25.3446 14.4713L25.917 17.8089C26.1276 19.0374 24.8381 19.9743 23.7349 19.3942L20.7376 17.8185C20.2994 17.5882 19.7761 17.5882 19.338 17.8185L16.3407 19.3942C15.2374 19.9743 13.948 19.0374 14.1586 17.8089L14.7311 14.4713C14.8148 13.9835 14.6531 13.4857 14.2986 13.1402L13.1646 12.0348" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M29.0625 5.39062L30.3516 2.36355" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M31.4258 7.8252L34.7201 6.75098" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M30.8516 11.4779L33.0716 12.2656" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M11.0156 5.39062L9.72656 2.36355" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M8.65169 7.8252L5.35742 6.75098" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9.22396 11.4779L7.00391 12.2656" stroke="white" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                    </svg>
                                </div>
                                <h4 class="title fw-semibold">Excellence</h4>
                                <p class="text-secondary">
                                    Excellence drives everything we do. From concept to execution, we pursue the highest standards in design, strategy, and delivery. Every detail matters, every decision is intentional, and every outcome reflects our commitment to quality. By continually refining our skills and processes, we ensure each project not only meets expectations but sets new benchmarks in creativity and performance.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /section-features -->
        </div>
        <!-- section-tools -->
        <div class="section-tools v2 flat-spacing">
            <div class="tool-wrap text-center">
                <div class="heading-section center mb-48">
                    <div class="heading-sub fw-semibold effectFade fadeUp">Tools</div>
                    <div class="heading-title text-gradient-3 effectFade fadeRotateX">We work with <br> powerful AI tools</div>
                </div>
                <div class="text effectFade fadeUp">
                    We design, build, and evaluate with a modern AI stack—LLMs, vector <br> search, orchestration, and observability—so your features are fast, <br> reliable, and secure.
                </div>
                <div class="position-relative tool-image-box">
                    <img class="img-item img-item-1" src="assets/images/section/tool-1.svg" alt="">
                    <img class="img-item img-item-2" src="assets/images/section/tool-2.svg" alt="">
                    <img class="img-item img-item-3" src="assets/images/section/tool-3.svg" alt="">
                    <div class="image-center flex-shrink">
                        <img src="assets/images/section/tool-center.svg" alt="">
                    </div>
                    <img class="img-item img-item-4" src="assets/images/section/tool-4.svg" alt="">
                    <img class="img-item img-item-5" src="assets/images/section/tool-5.svg" alt="">
                    <img class="img-item img-item-6" src="assets/images/section/tool-6.svg" alt="">
                    <div class="side-line">
                        <svg data-viewbox-desktop="0 0 302 157" data-viewbox-mobile="0 0 300 100" class="line-1" width="300" height="157" viewBox="0 0 302 157" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path id="line-path" d="M301 157V130C301 121.716 294.284 115 286 115H16C7.71572 115 0.999992 108.284 0.999992 100V0"
                                stroke="white" stroke-width="2" fill="none" />
                            <circle r="4" fill="#FD3A25">
                                <animateMotion dur="2.5s" repeatCount="indefinite"
                                    path="M301 157V130C301 121.716 294.284 115 286 115H16C7.71572 115 0.999992 108.284 0.999992 100V0" />
                            </circle>
                        </svg>
                        <svg data-viewbox-desktop="0 0 260 2" data-viewbox-mobile="0 0 250 2" class="line-2" width="260" height="2" viewBox="0 0 260 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M260 1H0" stroke="white" stroke-width="2" fill="none" />
                            <circle r="4" fill="#FD3A25">
                                <animateMotion dur="2.5s" repeatCount="indefinite" path="M260 1H0" />
                            </circle>
                        </svg>
                        <svg data-viewbox-desktop="0 0 302 159" data-viewbox-mobile="0 0 300 100" class="line-3" width="302" height="159" viewBox="0 0 302 159" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M301 0V29
                                     C301 37.2843 294.284 44 286 44
                                     H16
                                     C7.71572 44 0.999992 50.7157 0.999992 59
                                     V159" stroke="white" stroke-width="2" fill="none" />
                            <circle r="4" fill="#FD3A25">
                                <animateMotion dur="2.5s" repeatCount="indefinite" path="M301 0V29
                                          C301 37.2843 294.284 44 286 44
                                          H16
                                          C7.71572 44 0.999992 50.7157 0.999992 59
                                          V159" />
                            </circle>
                        </svg>
                        <svg data-viewbox-desktop="0 0 302 156" data-viewbox-mobile="0 0 300 100" class="line-4" width="302" height="156" viewBox="0 0 302 156" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 156V130
                                   C1 121.716 7.71573 115 16 115
                                   H286
                                   C294.284 115 301 108.284 301 100
                                   V0" stroke="white" stroke-width="2" fill="none" />
                            <circle r="4" fill="#FD3A25">
                                <animateMotion dur="2.5s" repeatCount="indefinite" path="M1 156V130
                                    C1 121.716 7.71573 115 16 115
                                    H286
                                    C294.284 115 301 108.284 301 100
                                    V0" />
                            </circle>
                        </svg>
                        <svg data-viewbox-desktop="0 0 260 3" data-viewbox-mobile="0 0 260 3" class="line-5" width="260" height="3" viewBox="0 0 260 3" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 1.05908H260" stroke="white" stroke-width="2.11844" fill="none" />
                            <circle r="3" fill="#FD3A25">
                                <animateMotion dur="2.5s" repeatCount="indefinite" path="M0 1.05908H260" />
                            </circle>
                        </svg>
                        <svg data-viewbox-desktop="0 0 302 155" data-viewbox-mobile="0 0 300 100" class="line-6" width="302" height="155" viewBox="0 0 302 155" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 0V25
                                     C1 33.2843 7.71573 40 16 40
                                     H286
                                     C294.284 40 301 46.7157 301 55
                                     V155" stroke="white" stroke-width="2" fill="none" />
                            <circle r="4" fill="#FD3A25">
                                <animateMotion dur="2.5s" repeatCount="indefinite" path="M1 0V25
                                          C1 33.2843 7.71573 40 16 40
                                          H286
                                          C294.284 40 301 46.7157 301 55
                                          V155" />
                            </circle>
                        </svg>
                    </div>
                </div>
                <a href="contact.html" class="tf-btn effectFade fadeRotateX">
                    Get Started
                </a>
            </div>
        </div>
        <!-- /section-tools -->
        <div class="box-black">
            <div class="light-box"></div>
            <img class="light-top" src="assets/images/item/light-top.png" alt="">
            <img class="light-bot" src="assets/images/item/light-bot.png" alt="">
            
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
                                    <img src="assets/images/team/team-1.jpg" alt="">
                                </div>
                                <a href="#" class="name h6 fw-semibold">Ava Collins</a>
                                <div class="sub text-body-1">Aigocy’s Design Lead</div>
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
                                        <img src="assets/images/team/team-2.jpg" alt="">
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
                                        <img src="assets/images/team/team-3.jpg" alt="">
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
                                        <img src="assets/images/team/team-4.jpg" alt="">
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
                                        <img src="assets/images/team/team-5.jpg" alt="">
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
                                <img src="assets/images/partner/partner-7.svg" alt="">
                            </div>
                            <div class="title text-body-1 text-white">Innovation in AI</div>
                            <div class="text text-body-1 text-white">Underwriting Risk Copilot (Fintech)</div>
                            <div class="year text-body-1 text-neutral-400">/ 2025</div>
                        </div>
                        <div class="awards-item effectFade fadeUp" data-delay="0.1">
                            <div class="image">
                                <img src="assets/images/partner/partner-8.svg" alt="">
                            </div>
                            <div class="title text-body-1 text-white">Best AI Product Design</div>
                            <div class="text text-body-1 text-white">Support Copilot (SaaS)</div>
                            <div class="year text-body-1 text-neutral-400">/ 2025</div>
                        </div>
                        <div class="awards-item effectFade fadeUp" data-delay="0.2">
                            <div class="image">
                                <img src="assets/images/partner/partner-9.svg" alt="">
                            </div>
                            <div class="title text-body-1 text-white">Data & AI Excellence</div>
                            <div class="text text-body-1 text-white">Clinical Note Summarizer (Healthcare)</div>
                            <div class="year text-body-1 text-neutral-400">/ 2024</div>
                        </div>
                        <div class="awards-item effectFade fadeUp" data-delay="0.3">
                            <div class="image">
                                <img src="assets/images/partner/partner-10.svg" alt="">
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
                                                        <img class="line-left" src="assets/images/item/line-1.png" alt="">
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
                                                        <img class="line-left" src="assets/images/item/line-1.png" alt="">
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
                                                        <img class="line-left" src="assets/images/item/line-1.png" alt="">
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
                                            <img src="assets/images/section/tes-1.jpg" alt="">
                                        </div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div class="testimonial-image">
                                            <img src="assets/images/section/tes-2.jpg" alt="">
                                        </div>
                                    </div>
                                    <div class="swiper-slide">
                                        <div class="testimonial-image">
                                            <img src="assets/images/section/tes-3.jpg" alt="">
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
        <!-- section-contact -->
        <div id="contact" class="flat-spacing pt-0">
            <div class="section-contact">
                <div class="contact-image">
                    <img src="assets/images/section/contact-image-bg.jpg" alt="">
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