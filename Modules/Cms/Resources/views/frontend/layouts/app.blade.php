<!doctype html>
<html lang="en">
    <head>

        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- custom metas -->
        @if(!empty($__site_details['meta_tags']))
            {!!$__site_details['meta_tags']!!}
        @endif

        @yield('meta')

        <!-- font awesome 5 free -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css"/>
        <!-- Bootstrap 5 -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title') | {{config('app.name', 'ECPACK')}}</title>
        <!-- custom css code -->
        @if(!empty($__site_details['custom_css']))
            {!!$__site_details['custom_css']!!}
        @endif

        <!-- in app chat widget css -->
        <!-- font -->
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/fonts/fonts.css') }}">
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/icon/icomoon/style.css') }}">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- css -->
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/swiper-bundle.min.css') }}">
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/animate.css') }}">
        
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/slick.css') }}">
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/slick.theme.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('modules/cms/assets/css/styles.css') }}">
        
    </head>
    <body class="counter-scroll">
        <canvas class="cursor-trail" id="trail" style="display: none;"></canvas>
    
        <!-- Scroll Top -->
        <button id="goTop">
            <span class="border-progress"></span>
            <span class="ic-wrap">
                <span class="icon icon-long-arrow-alt-up-solid"></span>
            </span>
        </button>
        <!-- /Scroll Top -->

        <!-- #wrapper -->
        <main id="wrapper">

            @includeIf('cms::frontend.layouts.header')
            
            @yield('content')   

            @includeIf('cms::frontend.layouts.footer')

        </main>
        <!-- /wrapper -->


        <!-- Mobile Menu -->
        <div class="offcanvas-menu">
            <div class="offcanvas-content">
                <div class="container h-100">
                    <div class="offcanvas-content_wrapin">
                        <div class="canvas_head">
                            <a href="{{ url('/') }}" class="logo-site">
                                <i class="icon icon-davies-logo"></i>
                            </a>
                            <div class="btn-mobile-menu close-mb-menu text-caption link">
                                <i class="icon icon-close"></i>
                                CLOSE
                            </div>
                        </div>
                        <div class="canvas_center">
                            <ul class="nav-ul-mb" id="mobile-menu">
                                <li>
                                    <div class="item">
                                        <div class="has-sub-menu">
                                            <a href="#dropdown-menu-index" class="mb-menu-link text-display-1 collapsed" data-bs-toggle="collapse"
                                                aria-expanded="false" aria-controls="dropdown-menu-index">
                                                <span class="text">Home</span>
                                            </a>
                                            <div id="dropdown-menu-index" class="collapse" data-bs-parent="#mobile-menu">
                                                <ul class="sub-nav-menu">
                                                    <li><a href="{{ url('/') }}" class="sub-nav-link text-white">Home</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="item">
                                        <a href="{{ url('c/page/about') }}" class="mb-menu-link text-display-1">
                                            <span class="text">About</span>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="item">
                                        <div class="has-sub-menu">
                                            <a href="#dropdown-menu-1" class="mb-menu-link text-display-1 collapsed" data-bs-toggle="collapse"
                                                aria-expanded="false" aria-controls="dropdown-menu-1">
                                                <span class="text">Works</span>
                                            </a>
                                            <div id="dropdown-menu-1" class="collapse" data-bs-parent="#mobile-menu">
                                                <ul class="sub-nav-menu">
                                                    <li><a href="{{ url('c/page/works') }}" class="sub-nav-link text-white">Works</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="item">
                                        <div class="has-sub-menu">
                                            <a href="#dropdown-menu-2" class="mb-menu-link text-display-1 collapsed" data-bs-toggle="collapse"
                                                aria-expanded="false" aria-controls="dropdown-menu-2">
                                                <span class="text">Services</span>
                                            </a>
                                            <div id="dropdown-menu-2" class="collapse" data-bs-parent="#mobile-menu">
                                                <ul class="sub-nav-menu">
                                                    <li><a href="{{ url('c/page/services') }}" class="sub-nav-link text-white">Services</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="item">
                                        <div class="has-sub-menu">
                                            <a href="#dropdown-menu-3" class="mb-menu-link text-display-1 collapsed" data-bs-toggle="collapse"
                                                aria-expanded="false" aria-controls="dropdown-menu-3">
                                                <span class="text">Blog</span>
                                            </a>
                                            <div id="dropdown-menu-3" class="collapse" data-bs-parent="#mobile-menu">
                                                <ul class="sub-nav-menu">
                                                    <li><a href="{{ url('c/blogs') }}" class="sub-nav-link text-white">Blog</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="item">
                                        <a href="{{ route('cms.contact.us') }}" class="mb-menu-link text-display-1">
                                            <span class="text">Contact</span>
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="canvas_foot">
                            <div class="left">
                                <a href="mailto:aigocy@gmail.com" class="text-caption text-neutral-200">aigocy@gmail.com</a>
                                <p class="text-caption text-neutral-200">
                                    CUP <span class="clock"></span>
                                </p>
                            </div>
                            <div class="right">
                                <a href="#" class="tf-link-icon text-caption text-neutral-200">
                                    <i class="icon icon-arrow-top-right"></i>
                                    TWITTER (X)
                                </a>
                                <a href="#" class="tf-link-icon text-caption text-neutral-200">
                                    <i class="icon icon-arrow-top-right"></i>
                                    DRIBBBLE
                                </a>
                                <a href="#" class="tf-link-icon text-caption text-neutral-200">
                                    <i class="icon icon-arrow-top-right"></i>
                                    LINKEDIN
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Mobile Menu -->

        <!-- Javascript -->
        <script src="{{ asset('modules/cms/assets/js/jquery.min.js') }}"></script>

        <script src="{{ asset('modules/cms/assets/js/bootstrap.min.js') }}"></script>

        <script src="{{ asset('modules/cms/assets/js/jquery.nice-select.min.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/swiper-bundle.min.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/slick.min.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/countto.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/carousel.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/infinityslide.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/ScrollSmooth.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/gsap.min.js') }}"></script>
        
        <script src="{{ asset('modules/cms/assets/js/ScrollTrigger.min.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/ScrollToPlugin.min.js') }}"></script>
        <script src="{{ asset('modules/cms/assets/js/gsapAnimation.js') }}"></script>

        <script src="{{ asset('modules/cms/assets/js/main.js') }}"></script>


        <!-- Google analytics code -->
        @if(!empty($__site_details['google_analytics']))
            {!!$__site_details['google_analytics']!!}
        @endif

        <!-- facebook pixel code -->
        @if(!empty($__site_details['fb_pixel']))
            {!!$__site_details['fb_pixel']!!}
        @endif

        <!-- custom js -->
        @if(!empty($__site_details['custom_js']))
            {!!$__site_details['custom_js']!!}
        @endif

        <!-- 3rd party chat_widget -->
        @if(
            (
                isset($__site_details['chat']) && 
                isset($__site_details['chat']['enable']) && 
                $__site_details['chat']['enable'] == 'other' &&
                !empty($__site_details['chat_widget'])
            ) ||
            (
                !isset($__site_details['chat']) &&
                empty($__site_details['chat']) &&
                !empty($__site_details['chat_widget'])
            )
        )
            {!!$__site_details['chat_widget']!!}
        @endif
        <!-- in app chat js -->
        @if(
            isset($__site_details['chat']) && 
            isset($__site_details['chat']['enable']) && 
            $__site_details['chat']['enable'] == 'in_app_chat'
        )
            @includeIf('cms::components.chat_widget.js.chat_widget-style1')
        @endif
        @yield('javascript')
    </body>
</html>