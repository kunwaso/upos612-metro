        <!-- start page title -->
        <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center position-relative page-title-extra-large">
                        <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">Wishlist</h1>
                    </div>
                    <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                        <ul>
                            <li><a href="{{ route('cms.home') }}">Home</a></li> 
                            <li>Wishlist</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->  
         <!-- start section -->
        <section>
            <div class="container">
                <div class="row"> 
                    <div class="col-12"> 
                        <ul class="shop-boxed shop-wrapper grid-loading grid grid-4col xxl-grid-4col xl-grid-3col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <li class="grid-sizer"></li>
                            <li class="grid-item w-100">
                                <div class="shop-box pb-25px">
                                    <p class="text-center alt-font text-dark-gray mb-20px">{{ __('cms::lang.storefront_empty_wishlist') }}</p>
                                    <div class="text-center">
                                        <a href="{{ route('cms.store.shop') }}" class="btn btn-dark-gray btn-medium btn-switch-text btn-round-edge btn-box-shadow">
                                            <span><span class="btn-double-text" data-text="{{ __('cms::lang.storefront_browse_shop') }}">{{ __('cms::lang.storefront_browse_shop') }}</span></span>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        </ul> 
                    </div> 
                </div>
            </div>
        </section>
        <!-- end section -->
