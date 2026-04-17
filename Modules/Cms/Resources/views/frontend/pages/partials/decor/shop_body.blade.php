        <!-- start page title -->
        <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center position-relative page-title-extra-large">
                        <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">Shop</h1>
                    </div>
                    <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                        <ul>
                            <li><a href="{{ route('cms.home') }}">Home</a></li> 
                            <li>shop</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->
        <!-- start section -->
        <section class="ps-6 pe-6 lg-ps-3 lg-pe-3 sm-ps-0 sm-pe-0">
            <div class="container-fluid">
                <div class="row flex-row-reverse"> 
                    <div class="col-xxl-10 col-lg-9 ps-5 md-ps-15px md-mb-60px">
                        <div class="toolbar-wrapper border-bottom border-color-extra-medium-gray d-flex flex-column flex-sm-row align-items-center w-100 mb-40px md-mb-30px pb-15px" data-anime='{ "translateY": [0, 0], "opacity": [0,1], "duration": 600, "delay":50, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <div class="xs-mb-10px">
                                <a href="#" class="me-10px"><img src="{{ asset('modules/cms/assets/images/shop-two-column.svg') }}" class="opacity-5" alt="" /></a>
                                <a href="#" class="me-10px"><img src="{{ asset('modules/cms/assets/images/shop-three-column.svg') }}" class="opacity-5" alt="" /></a>
                                <a href="#" class="me-10px"><img src="{{ asset('modules/cms/assets/images/shop-four-column.svg') }}" class="opacity-5" alt="" /></a>
                                <a href="#"><img src="{{ asset('modules/cms/assets/images/shop-list.svg') }}" class="opacity-5" alt="" /></a>
                            </div>
                            <div class="ms-20px xs-ms-0">{{ $resultsSummary ?? '' }}</div>
                            @if(!empty($catalogSearch))
                            <div class="ms-20px xs-ms-0">
                                <span class="badge border border-color-extra-medium-gray text-dark-gray fw-500">
                                    Search: {{ $catalogSearch }}
                                </span>
                            </div>
                            @endif
                            @if(!empty($catalogCategory))
                            <div class="ms-20px xs-ms-0">
                                <span class="badge border border-color-extra-medium-gray text-dark-gray fw-500">
                                    Category: {{ $catalogCategory }}
                                </span>
                            </div>
                            @endif
                            <div class="mx-auto me-sm-0">
                                <form method="get" action="{{ route('cms.store.shop') }}" class="d-inline-block">
                                    @if(!empty($catalogCategory))
                                        <input type="hidden" name="category" value="{{ $catalogCategory }}">
                                    @endif
                                    @if(!empty($catalogSearch))
                                        <input type="hidden" name="s" value="{{ $catalogSearch }}">
                                    @endif
                                    <select name="sort" class="form-select border-0 background-position-right" aria-label="Sorting" onchange="this.form.submit()">
                                        <option value="latest" @selected(($catalogSort ?? 'latest') === 'latest')>Sort by latest</option>
                                        <option value="name" @selected(($catalogSort ?? '') === 'name')>Sort by name</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                        <ul class="shop-boxed shop-wrapper grid-loading grid grid-4col xxl-grid-4col xl-grid-3col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <li class="grid-sizer"></li>
                            @forelse($products ?? [] as $card)
                            @include('cms::frontend.pages.partials.storefront.product_card', ['card' => $card])
                            @empty
                            <li class="grid-item w-100">
                                <p class="text-center alt-font text-dark-gray py-5 mb-0">{{ __('cms::lang.storefront_no_products') }}</p>
                            </li>
                            @endforelse
                        </ul>
                        @if(isset($products) && $products->hasPages())
                        <div class="w-100 d-flex mt-3 justify-content-center" data-anime='{ "translateY": [0, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            {{ $products->appends(request()->except('page'))->links() }}
                        </div>
                        @endif
                    </div>
                    <div class="col-xxl-2 col-lg-3 shop-sidebar">
                        <div class="mb-30px">
                            <span class="alt-font fw-600 fs-17 text-dark-gray d-block mb-10px">Filter by categories</span>
                            <ul class="fs-15 shop-filter category-filter">
                                <li><a href="{{ route('cms.store.shop', ['category' => 'Bao Bì dạng cuộn']) }}"><span class="product-cb product-category-cb"></span>Bao Bì dạng cuộn</a><span class="item-qty">02</span></li>
                                <li><a href="{{ route('cms.store.shop', ['category' => 'Thùng - hộp']) }}"><span class="product-cb product-category-cb"></span>Thùng - hộp</a><span class="item-qty">03</span></li>
                                <li><a href="{{ route('cms.store.shop', ['category' => 'Dây dai - cuộn']) }}"><span class="product-cb product-category-cb"></span>Dây dai - cuộn</a><span class="item-qty">05</span></li>
                                <li><a href="{{ route('cms.store.shop', ['category' => 'Chống sốc - Gel']) }}"><span class="product-cb product-category-cb"></span>Chống sốc - Gel</a><span class="item-qty">07</span></li>
                                <li><a href="{{ route('cms.store.shop', ['category' => 'Bao bì khác']) }}"><span class="product-cb product-category-cb"></span>Bao bì khác</a><span class="item-qty">08</span></li>
                                <li><a href="{{ route('cms.store.shop', ['category' => 'Dụng cụ']) }}"><span class="product-cb product-category-cb"></span>Dụng cụ</a><span class="item-qty">09</span></li>
                            </ul>
                        </div>
                        <div class="mb-30px">
                            <div class="d-flex align-items-center mb-20px">
                                <span class="alt-font fw-600 fs-17 text-dark-gray">New arrivals</span>
                                <div class="d-flex ms-auto">
                                    <!-- start slider navigation -->
                                    <div class="slider-one-slide-prev-1 swiper-button-prev slider-navigation-style-08 me-5px"><i class="fa-solid fa-arrow-left text-dark-gray"></i></div>
                                    <div class="slider-one-slide-next-1 swiper-button-next slider-navigation-style-08 ms-5px"><i class="fa-solid fa-arrow-right text-dark-gray"></i></div>
                                    <!-- end slider navigation -->
                                </div> 
                            </div>
                            <div class="swiper slider-one-slide" data-slider-options='{ "slidesPerView": 1, "loop": true, "autoplay": { "delay": 5000, "disableOnInteraction": false }, "navigation": { "nextEl": ".slider-one-slide-next-1", "prevEl": ".slider-one-slide-prev-1" }, "keyboard": { "enabled": true, "onlyInViewport": true }, "effect": "slide" }'>
                                <div class="swiper-wrapper">
                                    @forelse(($sidebarSlides ?? collect()) as $slideRow)
                                    <div class="swiper-slide">
                                        <div class="shop-filter new-arribals">
                                            @foreach($slideRow as $row)
                                            <div class="d-flex align-items-center {{ !$loop->last ? 'mb-20px' : '' }}">
                                                <figure class="mb-0">
                                                    <a href="{{ $row['url'] }}">
                                                        <img class="border-radius-4px w-80px" src="{{ $row['image_url'] }}" alt="{{ $row['name'] }}">
                                                    </a>
                                                </figure>
                                                <div class="col ps-25px">
                                                    <a href="{{ $row['url'] }}" class="text-dark-gray alt-font fw-600">{{ $row['name'] }}</a>
                                                    <div class="fw-500 fs-14 lh-normal">{{ $row['price_label'] }}</div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @empty
                                    <div class="swiper-slide">
                                        <div class="shop-filter new-arribals">
                                            <p class="text-dark-gray alt-font fs-14 mb-0">{{ __('cms::lang.storefront_no_products') }}</p>
                                        </div>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
