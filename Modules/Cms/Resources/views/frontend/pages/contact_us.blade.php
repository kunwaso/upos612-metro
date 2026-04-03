@extends('cms::frontend.layouts.app')
@section('title', __('cms::lang.contact_us'))
@section('meta')
    <meta name="description" content="{{ $page->meta_description ?? '' }}">
@endsection
@section('content')
        <!-- start page title -->
        <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center position-relative page-title-extra-large">
                        <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">{{ $page->title ?? __('Contact us') }}</h1>
                    </div>
                    <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                        <ul>
                            <li><a href="{{ route('cms.home') }}">Home</a></li>
                            <li>{{ $page->title ?? __('Contact us') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->
        <!-- start section -->
        <section>
            <div class="container">
                <div class="row row-cols-1 row-cols-lg-4 row-cols-sm-2" data-anime='{ "el": "childs", "translateX": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                    <div class="col md-mb-35px">
                        <span class="fs-17 fw-600 d-block w-90 sm-w-100 text-base-color border-bottom border-color-base-color pb-15px mb-15px"><i class="feather icon-feather-map-pin d-inline-block text-base-color me-10px"></i>Office location</span>
                        <div>
                            <p class="w-100 m-0">16122 Collins street,<br> Melbourne, Australia</p>
                        </div>
                    </div>
                    <div class="col md-mb-35px">
                        <span class="fs-17 fw-600 d-block w-90 sm-w-100 text-base-color border-bottom border-color-base-color pb-15px mb-15px"><i class="feather icon-feather-mail d-inline-block text-base-color me-10px"></i>Send a message</span>
                        <a href="mailto:info@example.com">info@example.com</a>
                    </div>
                    <div class="col xs-mb-35px">
                        <span class="fs-17 fw-600 d-block w-90 sm-w-100 text-base-color border-bottom border-color-base-color pb-15px mb-15px"><i class="feather icon-feather-phone d-inline-block text-base-color me-10px"></i>Call us directly</span>
                        <a href="tel:1800222000">1-800-222-000</a><br>
                        <a href="tel:1800222002">1-800-222-002</a>
                    </div>
                    <div class="col">
                        <span class="fs-17 fw-600 d-block w-90 sm-w-100 text-base-color border-bottom border-color-base-color pb-15px mb-15px"><i class="feather icon-feather-users d-inline-block text-base-color me-10px"></i>Join our team</span>
                        <a href="mailto:careers@example.com">careers@example.com</a>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
        <!-- start section -->
        <section class="pt-0 position-relative overflow-hidden">
            <div class="container">
                <div class="row mb-20px">
                    <div class="col-lg-10 col-md-12" data-anime='{ "effect": "slide", "color": "#1B3250", "direction":"rl", "easing": "easeOutQuad", "delay":50}'>
                        <img src="{{ asset('modules/cms/assets/images/demo-decor-store-contact-01.jpg') }}" alt="" />
                    </div>
                </div>
                <div class="row align-items-end">
                    <div class="col-lg-7 col-md-12 align-self-start md-mt-15px" data-bottom-top="transform: translate3d(80px, 20px, 0px);" data-top-bottom="transform: translate3d(-80px, 20px, 0px);">
                        <span class="alt-font fs-120 xs-fs-75 fw-600 opacity-3 d-block d-lg-inline-block text-center ls-minus-3px text-white-space-nowrap xs-text-white-space-normal">Get in touch!</span>
                    </div>
                    <div class="col-lg-5 contact-form-style-03 position-relative overlap-section-one-fourth md-mt-0" data-anime='{ "el": "childs", "translateY": [50, 0],"opacity": [0,1], "duration": 800, "delay": 550, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <div class="bg-base-color p-16 lg-p-10 position-relative overflow-hidden mt-50px">
                            <i class="bi bi-chat-text fs-140 text-white opacity-1 position-absolute top-minus-30px right-minus-20px"></i>
                            <h2 class="fw-600 alt-font text-white mb-30px fancy-text-style-4 ls-minus-1px">Say
                                <span data-fancy-text='{ "effect": "rotate", "string": ["hello!", "hallå!", "salve!"] }'></span>
                            </h2>
                            <div class="alert mt-4 alert-primary enquire_response_alt text-white border border-white" role="alert" style="display:none;">
                                <span class="enquire_response"></span>
                            </div>
                            <form id="contact_form" method="post">
                                @csrf
                                <div class="position-relative form-group mb-10px">
                                    <span class="form-icon text-white"><i class="bi bi-person icon-small"></i></span>
                                    <input class="fw-300 ps-0 border-radius-0px bg-transparent border-color-transparent-white-light placeholder-medium-gray form-control required" type="text" name="name" placeholder="Your name*" required>
                                </div>
                                <div class="position-relative form-group mb-10px">
                                    <span class="form-icon text-white"><i class="bi bi-phone icon-small"></i></span>
                                    <input class="fw-300 ps-0 border-radius-0px bg-transparent border-color-transparent-white-light placeholder-medium-gray form-control required" type="text" name="mobile" placeholder="Mobile*" required>
                                </div>
                                <div class="position-relative form-group mb-10px">
                                    <span class="form-icon text-white"><i class="bi bi-envelope icon-small"></i></span>
                                    <input class="fw-300 ps-0 border-radius-0px bg-transparent border-color-transparent-white-light placeholder-medium-gray form-control required" type="email" name="email" placeholder="Your email address*" required>
                                </div>
                                <div class="position-relative form-group form-textarea mt-10px">
                                    <textarea class="fw-300 ps-0 border-radius-0px bg-transparent border-color-transparent-white-light placeholder-medium-gray form-control" name="message" placeholder="Your message" rows="3" required></textarea>
                                    <span class="form-icon text-white"><i class="bi bi-chat-square-dots icon-small"></i></span>
                                    <button id="submit-btn" class="btn btn-white btn-large fw-600 btn-switch-text btn-box-shadow btn-round-edge submit mt-30px" type="submit">
                                        <span>
                                            <span class="btn-double-text" data-text="Send message">Send message</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
        <!-- start section -->
        <section class="p-0 border-radius-6px lg-no-border-radius overflow-hidden" id="location">
            <div class="container-fluid px-0">
                <div class="row justify-content-center g-0">
                    <div class="col-12 p-0">
                        <div id="map" class="map" data-map-options='{ "lat": -37.817240, "lng": 144.955820, "style": "Silver", "marker": { "type": "HTML", "color": "#1B3250" }, "popup": { "defaultOpen": true, "html": "<div class=infowindow><strong class=\"mb-3 d-inline-block alt-font\">Crafto Decor Store</strong><p class=\"alt-font\">16122 Collins street, Melbourne, Australia</p></div><div class=\"google-maps-link alt-font\"> <a aria-label=\"View larger map\" target=\"_blank\" jstcache=\"31\" href=_https_/maps.google.com/mapse283.html?ll=-37.805688,144.962312&amp;z=17&amp;t=m&amp;hl=en-US&amp;gl=IN&amp;mapclient=embed&amp;cid=13153204942596594449\%22 jsaction=\"mouseup:placeCard.largerMap\">VIEW LARGER MAP</a></div>" } }'></div>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
@endsection
@section('javascript')
<script type="text/javascript">
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $("#contact_form").on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            let data = form.serialize();
            $("#submit-btn").attr('disabled', true);
            $.ajax({
                method: 'POST',
                dataType: "json",
                url: "{{ route('cms.submit.contact.form') }}",
                data: data,
                success: function(result){
                    $("#submit-btn").attr('disabled', false);
                    $(".enquire_response_alt").css({ 'display' : '' });
                    $(".enquire_response").text(result.msg);
                    if (result.success) {
                        form.trigger("reset");
                    }
                },
                error: function() {
                    $("#submit-btn").attr('disabled', false);
                    $(".enquire_response_alt").css({ 'display' : '' });
                    $(".enquire_response").text('{{ __('messages.something_went_wrong') }}');
                }
            });
        });
    });
</script>
@endsection
