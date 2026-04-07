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

        <!-- google fonts preconnect -->
        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <!-- slider revolution CSS files -->
        <link rel="stylesheet" type="text/css" href="{{ asset('modules/cms/assets/revolution/css/settings.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('modules/cms/assets/revolution/css/layers.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('modules/cms/assets/revolution/css/navigation.css') }}">
        <!-- style sheets and font icons  -->
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/vendors.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/icon.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/style.css') }}"/>
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/css/responsive.css') }}"/>
        <link rel="stylesheet" href="{{ asset('modules/cms/assets/style/styles.css') }}"/>
        
    </head>
        <body data-mobile-nav-style="classic">
        <!-- start header -->
        @include('cms::frontend.layouts.header')
        <!-- end header -->
        @yield('content')
        <!-- start footer -->
        @include('cms::frontend.layouts.footer')
        <!-- end footer -->

        <!-- start scroll progress -->
        <div class="scroll-progress d-none d-xxl-block">
            <a href="#" class="scroll-top" aria-label="scroll">
                <span class="scroll-text">Scroll</span><span class="scroll-line"><span class="scroll-point"></span></span>
            </a>
        </div>
        <!-- end scroll progress -->


        <!-- /Mobile Menu -->

        <!-- Javascript -->
        <script type="text/javascript" src="{{ asset('modules/cms/assets/js/jquery.js') }}"></script>
        <script type="text/javascript" src="{{ asset('modules/cms/assets/js/vendors.min.js') }}"></script>
        <!-- slider revolution core javaScript files -->
        <script type="text/javascript" src="{{ asset('modules/cms/assets/revolution/js/jquery.themepunch.tools.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('modules/cms/assets/revolution/js/jquery.themepunch.revolution.min.js') }}"></script>

        <!-- Slider Revolution add on files -->
        <script type='text/javascript' src='{{ asset('modules/cms/assets/revolution/revolution-addons/particles/js/revolution.addon.particles.min.js') }}?ver=1.0.3'></script>
        <!-- Slider's main "init" script -->
        <script type="text/javascript">
            /* https://learn.jquery.com/using-jquery-core/document-ready/ */
            jQuery(document).ready(function () {
                /* initialize the slider based on the Slider's ID attribute from the wrapper above */
                jQuery('#decor-store-slider').show().revolution({
                    sliderType: "standard",
                    /* sets the Slider's default timeline */
                    delay: 9000,
                    /* options are 'auto', 'fullwidth' or 'fullscreen' */
                    sliderLayout: 'fullscreen',
                    /* RESPECT ASPECT RATIO */
                    autoHeight: 'off',
                    /* options that disable autoplay */
                    stopLoop: "on",
                    stopAfterLoops: 0,
                    stopAtSlide: 1,
                    navigation: {
                        keyboardNavigation: "on",
                        keyboard_direction: "horizontal",
                        mouseScrollNavigation: "off",
                        mouseScrollReverse: "default",
                        onHoverStop: "off",
                        touch: {
                            touchenabled: "on",
                            touchOnDesktop: "on",
                            swipe_threshold: 75,
                            swipe_min_touches: 1,
                            swipe_direction: "horizontal",
                            drag_block_vertical: true
                        },
                        arrows: {

                            enable: false,
                            style: 'uranus',
                            rtl: false,
                            hide_onleave: false,
                            hide_onmobile: false,
                            hide_under: 0,
                            hide_over: 778,
                            hide_delay: 200,
                            hide_delay_mobile: 1200,
                            left: {
                                container: 'slider',
                                h_align: 'left',
                                v_align: 'center',
                                h_offset: 10,
                                v_offset: 10
                            },
                            right: {
                                container: 'slider',
                                h_align: 'right',
                                v_align: 'center',
                                h_offset: 10,
                                v_offset: 10
                            }

                        }

                    },
                    /* Lazy Load options are "all", "smart", "single" and "none" */
                    lazyType: "smart",
                    spinner: "spinner0",
                    /* DISABLE FORCE FULL-WIDTH */
                    fullScreenAlignForce: 'off',
                    hideThumbsOnMobile: 'off',
                    hideSliderAtLimit: 0,
                    hideCaptionAtLimit: 0,
                    hideAllCaptionAtLilmit: 0,
                    /* [DESKTOP, LAPTOP, TABLET, SMARTPHONE] */
                    responsiveLevels: [1240, 1024, 778, 480],
                    /* [DESKTOP, LAPTOP, TABLET, SMARTPHONE] */
                    gridwidth: [1220, 1024, 778, 480],
                    /* [DESKTOP, LAPTOP, TABLET, SMARTPHONE] */
                    gridheight: [900, 1000, 960, 720],
                    /* [DESKTOP, LAPTOP, TABLET, SMARTPHONE] */
                    visibilityLevels: [1240, 1024, 1024, 480],
                    fallbacks: {
                        simplifyAll: 'on',
                        nextSlideOnWindowFocus: 'off',
                        disableFocusListener: false
                    },
                });
            });
        </script>
        <script type="text/javascript" src="{{ asset('modules/cms/assets/js/main.js') }}"></script>



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
