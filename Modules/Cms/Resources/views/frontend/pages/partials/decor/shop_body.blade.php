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
                            <div class="ms-20px xs-ms-0">Showing 1–12 of 48 results</div>
                            <div class="mx-auto me-sm-0">
                                <select class="form-select border-0 background-position-right" aria-label="Default sorting">
                                    <option selected>Default sorting</option>
                                    <option value="1">Sort by popularity</option>
                                    <option value="2">Sort by average rating</option>
                                    <option value="3">Sort by latest</option>
                                    <option value="4">Sort by price: low to high</option>
                                    <option value="5">Sort by price: high to low</option>
                                </select>
                            </div>
                        </div>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
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
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-11.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Watch box</a>
                                        <div class="fw-500 fs-15 lh-normal">$22.00</div>
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
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-02.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Modern stool</a>
                                        <div class="fw-500 fs-15 lh-normal">$19.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}">
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-03.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Nutcracker</a>
                                        <div class="fw-500 fs-15 lh-normal">$28.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="{{ route('cms.store.product') }}"> 
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-15.jpg') }}" alt="" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist"><i class="feather icon-feather-heart fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to cart"><i class="feather icon-feather-shopping-bag fs-15"></i></a>
                                            <a href="#" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top" title="Quick shop"><i class="feather icon-feather-eye fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="{{ route('cms.store.product') }}" class="text-dark-gray fs-17 alt-font fw-600">Decor lamp</a>
                                        <div class="fw-500 fs-15 lh-normal">$12.00</div>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
                        </ul>
                        <div class="w-100 d-flex mt-3 justify-content-center" data-anime='{ "translateY": [0, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <ul class="pagination pagination-style-01 fs-13 fw-500 mb-0">
                                <li class="page-item"><a class="page-link" href="#"><i class="feather icon-feather-arrow-left fs-18 d-xs-none"></i></a></li>
                                <li class="page-item"><a class="page-link" href="#">01</a></li>
                                <li class="page-item active"><a class="page-link" href="#">02</a></li>
                                <li class="page-item"><a class="page-link" href="#">03</a></li>
                                <li class="page-item"><a class="page-link" href="#">04</a></li>
                                <li class="page-item"><a class="page-link" href="#"><i class="feather icon-feather-arrow-right fs-18 d-xs-none"></i></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xxl-2 col-lg-3 shop-sidebar">
                        <div class="mb-30px">
                            <span class="alt-font fw-600 fs-17 text-dark-gray d-block mb-10px">Filter by categories</span>
                            <ul class="fs-15 shop-filter category-filter">
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Chairs</a><span class="item-qty">22</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Decor</a><span class="item-qty">28</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Furnitures</a><span class="item-qty">36</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Lighting</a><span class="item-qty">24</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Sofas</a><span class="item-qty">26</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Stools</a><span class="item-qty">33</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Mirrors</a><span class="item-qty">22</span></li>
                            </ul>
                        </div>
                        <div class="mb-30px">
                            <span class="alt-font fw-600 fs-17 text-dark-gray d-block mb-10px">Filter by color</span>
                            <ul class="fs-15 shop-filter color-filter">
                                <li><a href="#"><span class="product-cb product-color-cb" style="background-color:#232323"></span>Black</a><span class="item-qty">05</span></li>
                                <li><a href="#"><span class="product-cb product-color-cb" style="background-color:#8E412E"></span>Chestnut</a><span class="item-qty">24</span></li>
                                <li><a href="#"><span class="product-cb product-color-cb" style="background-color:#E0A699"></span>Brown</a><span class="item-qty">32</span></li>
                                <li><a href="#"><span class="product-cb product-color-cb" style="background-color:#E0A699"></span>Pastel pink</a><span class="item-qty">22</span></li>
                                <li><a href="#"><span class="product-cb product-color-cb" style="background-color:#9DA693"></span>Litchen green</a><span class="item-qty">09</span></li>
                                <li><a href="#"><span class="product-cb product-color-cb" style="background-color:#E7C06D"></span>Yellow</a><span class="item-qty">06</span></li> 
                            </ul>
                        </div>
                        <div class="mb-30px">
                            <span class="alt-font fw-600 fs-17 text-dark-gray d-block mb-10px">Filter by fabric</span>
                            <ul class="fs-15 shop-filter fabric-filter">
                                <li><a href="#"><span class="product-cb product-fabric-cb"><img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-listing-fabric-01.jpg') }}" alt=""/></span>Polyolefin</a><span class="item-qty">08</span></li> 
                                <li><a href="#"><span class="product-cb product-fabric-cb"><img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-listing-fabric-02.jpg') }}" alt=""/></span>Jute fabric</a><span class="item-qty">03</span></li>
                                <li><a href="#"><span class="product-cb product-fabric-cb"><img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-listing-fabric-03.jpg') }}" alt=""/></span>Crepe fabric</a><span class="item-qty">20</span></li>
                                <li><a href="#"><span class="product-cb product-fabric-cb"><img src="{{ asset('modules/cms/assets/images/demo-decor-store-product-listing-fabric-04.jpg') }}" alt=""/></span>Wollen fabric</a><span class="item-qty">08</span></li>
                            </ul>
                        </div>
                        <div class="mb-30px">
                            <span class="alt-font fw-600 fs-17 text-dark-gray d-block mb-10px">Filter by price</span>
                            <ul class="fs-15 shop-filter price-filter">
                                <li><a href="#"><span class="product-cb product-category-cb"></span>Under $25</a><span class="item-qty">08</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>$25 to $50</a><span class="item-qty">05</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>$50 to $100</a><span class="item-qty">25</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>$100 to $200</a><span class="item-qty">18</span></li>
                                <li><a href="#"><span class="product-cb product-category-cb"></span>$200 & Above</a><span class="item-qty">36</span></li>  
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
                                    <!-- start text slider item -->
                                    <div class="swiper-slide">
                                        <div class="shop-filter new-arribals"> 
                                            <div class="d-flex align-items-center mb-20px">
                                                <figure class="mb-0">
                                                    <a href="{{ route('cms.store.product') }}">
                                                        <img class="border-radius-4px w-80px" src="{{ asset('modules/cms/assets/images/demo-decor-store-product-01.jpg') }}" alt="">
                                                    </a>
                                                </figure>
                                                <div class="col ps-25px">
                                                    <a href="{{ route('cms.store.product') }}" class="text-dark-gray alt-font fw-600">Table clock</a>
                                                    <div class="fw-500 fs-14 lh-normal"><del class="me-5px">$30.00</del>$23.00</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-20px">
                                                <figure class="mb-0">
                                                    <a href="{{ route('cms.store.product') }}">
                                                        <img class="border-radius-4px w-80px" src="{{ asset('modules/cms/assets/images/demo-decor-store-product-14.jpg') }}" alt="">
                                                    </a>
                                                </figure>
                                                <div class="col ps-25px">
                                                    <a href="{{ route('cms.store.product') }}" class="text-dark-gray alt-font fw-600">Wood stool</a>
                                                    <div class="fw-500 fs-14 lh-normal"><del class="me-5px">$50.00</del>$43.00</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <figure class="mb-0">
                                                    <a href="{{ route('cms.store.product') }}">
                                                        <img class="border-radius-4px w-80px" src="{{ asset('modules/cms/assets/images/demo-decor-store-product-12.jpg') }}" alt="">
                                                    </a>
                                                </figure>
                                                <div class="col ps-25px">
                                                    <a href="{{ route('cms.store.product') }}" class="text-dark-gray alt-font fw-600">Wall clock</a>
                                                    <div class="fw-500 fs-14 lh-normal"><del class="me-5px">$20.00</del>$15.00</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end text slider item -->
                                    <!-- start text slider item -->
                                    <div class="swiper-slide">
                                        <div class="shop-filter new-arribals"> 
                                            <div class="d-flex align-items-center mb-20px">
                                                <figure class="mb-0">
                                                    <a href="{{ route('cms.store.product') }}">
                                                        <img class="border-radius-4px w-80px" src="{{ asset('modules/cms/assets/images/demo-decor-store-product-06.jpg') }}" alt="">
                                                    </a>
                                                </figure>
                                                <div class="col ps-25px">
                                                    <a href="{{ route('cms.store.product') }}" class="text-dark-gray alt-font fw-600">Ceramic pot</a>
                                                    <div class="fw-500 fs-14 lh-normal"><del class="me-5px">$15.00</del>$10.00</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-20px">
                                                <figure class="mb-0">
                                                    <a href="{{ route('cms.store.product') }}">
                                                        <img class="border-radius-4px w-80px" src="{{ asset('modules/cms/assets/images/demo-decor-store-product-09.jpg') }}" alt="">
                                                    </a>
                                                </figure>
                                                <div class="col ps-25px">
                                                    <a href="{{ route('cms.store.product') }}" class="text-dark-gray alt-font fw-600">Ceramic jar</a>
                                                    <div class="fw-500 fs-14 lh-normal"><del class="me-5px">$35.00</del>$30.00</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <figure class="mb-0">
                                                    <a href="{{ route('cms.store.product') }}">
                                                        <img class="border-radius-4px w-80px" src="{{ asset('modules/cms/assets/images/demo-decor-store-product-13.jpg') }}" alt="">
                                                    </a>
                                                </figure>
                                                <div class="col ps-25px">
                                                    <a href="{{ route('cms.store.product') }}" class="text-dark-gray alt-font fw-600">Classic stool</a>
                                                    <div class="fw-500 fs-14 lh-normal"><del class="me-5px">$20.00</del>$15.00</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end text slider item -->
                                </div>
                                <!-- start slider navigation --> 
                            </div> 
                        </div>
                        <div>
                            <span class="alt-font fw-600 fs-17 text-dark-gray d-block mb-10px">Filter by tags</span>
                            <div class="shop-filter tag-cloud"> 
                                <a href="#">furniture</a>
                                <a href="#">living room</a>
                                <a href="#">lamp</a>
                                <a href="#">modern</a>
                                <a href="#">wooden</a>
                                <a href="#">armchair</a>
                                <a href="#">dining room</a>
                                <a href="#">handmade</a>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
