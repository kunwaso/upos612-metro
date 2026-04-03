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
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-01.jpg') }}" alt="" />
                                            <span class="lable new">New</span>
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Table clock</a>
                                        <div class="fw-500 fs-15 lh-normal"><del>$30.00</del>$23.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-14.jpg') }}" alt="" /> 
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Wood stool</a>
                                        <div class="fw-500 fs-15 lh-normal">$54.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <span class="lable new">New</span>
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-12.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Ceramic mug</a>
                                        <div class="fw-500 fs-15 lh-normal"><del>$20.00</del>$15.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-05.jpg') }}" alt="" /> 
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Decorative plants</a>
                                        <div class="fw-500 fs-15 lh-normal"><del>$30.00</del>$35.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-06.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Ceramic pot</a>
                                        <div class="fw-500 fs-15 lh-normal">$23.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-13.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Ceramic plate</a>
                                        <div class="fw-500 fs-15 lh-normal"><del>$25.00</del>$15.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-09.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Ceramic container</a>
                                        <div class="fw-500 fs-15 lh-normal">$35.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-10.jpg') }}" alt="" />
                                            <span class="lable hot">Hot</span>
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart-on fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Design wall clock</a>
                                        <div class="fw-500 fs-15 lh-normal">$19.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item --> 
                        </ul> 
                    </div> 
                </div>
            </div>
        </section>
        <!-- end section -->
