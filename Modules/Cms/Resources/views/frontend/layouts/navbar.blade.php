<nav class="navbar navbar-expand-lg header-light bg-transparent disable-fixed" data-header-hover="light">
    <div class="container-fluid">
        <div class="col-auto">
            <a class="navbar-brand" href="{{ route('cms.home') }}">
                <img src="{{ asset('modules/cms/assets/images/demo-decor-store-logo-black.png') }}" data-at2x="{{ asset('modules/cms/assets/images/demo-decor-store-logo-black@2x.png') }}" alt="" class="default-logo">
                <img src="{{ asset('modules/cms/assets/images/demo-decor-store-logo-black.png') }}" data-at2x="{{ asset('modules/cms/assets/images/demo-decor-store-logo-black@2x.png') }}" alt="" class="alt-logo">
                <img src="{{ asset('modules/cms/assets/images/demo-decor-store-logo-black.png') }}" data-at2x="{{ asset('modules/cms/assets/images/demo-decor-store-logo-black@2x.png') }}" alt="" class="mobile-logo"> 
            </a>
        </div>
        <div class="col-auto menu-order position-static xs-ps-0">
            <button class="navbar-toggler float-start" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-line"></span>
                <span class="navbar-toggler-line"></span>
                <span class="navbar-toggler-line"></span>
                <span class="navbar-toggler-line"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav"> 
                <ul class="navbar-nav alt-font"> 
                    <li class="nav-item"><a href="{{ route('cms.home') }}" class="nav-link">Home</a></li>
                    <li class="nav-item dropdown submenu">
                        <a href="{{ route('cms.store.shop') }}" class="nav-link">Sản phẩm<span class="label bg-base-color text-white text-uppercase border-radius-26px">Hot</span></a>
                        <i class="fa-solid fa-angle-down dropdown-toggle" id="navbarDropdownMenuLink1" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                        <div class="dropdown-menu submenu-content" aria-labelledby="navbarDropdownMenuLink1"> 
                            <div class="d-lg-flex mega-menu m-auto flex-column">
                                <div class="row row-cols-1 row-cols-lg-5 mb-60px md-mb-30px sm-mb-20px">
                                    <div class="col">
                                        <ul>  
                                            <li class="sub-title">Bao Bì dạng cuộn</li>
                                            <li><a href="#">Cuộn Màng PE</a></li>
                                            <li><a href="#">Băng keo</a></li>
                                            <li><a href="#">Cuộn giấy chống sốc</a></li>
                                        </ul>
                                    </div>
                                    <div class="col">
                                        <ul>  
                                            <li class="sub-title">Thùng - hộp</li>
                                            <li><a href="#">Thùng carton</a></li>
                                            <li><a href="#">Hộp carton</a></li>
                                            <li><a href="#">Túi giấy</a></li>
                                        </ul>
                                    </div>
                                    <div class="col">
                                        <ul>  
                                            <li class="sub-title">Dây đai</li>
                                            <li><a href="#">Dây đai thép</a></li>
                                            <li><a href="#">Dây đai nhựa</a></li>
                                        </ul>
                                    </div>
                                    <div class="col">
                                        <ul>  
                                            <li class="sub-title">Chống sốc - Gel</li>
                                            <li><a href="#">Túi khí chống sốc</a></li>
                                            <li><a href="#">Gói hút ẩm silica gel</a></li>
                                        </ul>
                                    </div>
                                    <div class="col">
                                        <ul>  
                                            <li class="sub-title">Bao bì khác</li>
                                            <li><a href="#">pallet gỗ ép</a></li>
                                            <li><a href="#">Đóng khung gỗ</a></li>
                                            <li><a href="#">Dụng cụ đóng gói</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2">
                                    <div class="col">
                                        <a href="{{ route('cms.store.shop') }}"><img class="w-100" src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-banner-01.jpg') }}" alt=""></a>
                                    </div>
                                    <div class="col">
                                        <a href="{{ route('cms.store.shop') }}"><img class="w-100" src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-banner-02.jpg') }}" alt=""></a>
                                    </div>
                                </div>
                            </div> 
                        </div>
                    </li> 
                    <li class="nav-item dropdown submenu">
                        <a href="{{ route('cms.store.collections') }}" class="nav-link">Danh mục sản phẩm</a>
                        <i class="fa-solid fa-angle-down dropdown-toggle" id="navbarDropdownMenuLink2" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                        <div class="dropdown-menu submenu-content" aria-labelledby="navbarDropdownMenuLink2"> 
                            <div class="d-lg-flex mega-menu m-auto flex-column">
                                <div class="row row-cols-2 row-cols-lg-6 row-cols-sm-3 md-pt-15px align-items-center justify-content-center mb-60px md-mb-30px sm-mb-0">
                                    <div class="col md-mb-30px">
                                        <a href="{{ route('cms.store.collections') }}" class="text-center"> 
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-category-01.jpg') }}" alt="">  
                                        </a>
                                        <a href="{{ route('cms.store.collections') }}" class="btn btn-hover-animation text-uppercase-inherit fw-600 ls-0px justify-content-center">
                                            <span> 
                                                <span class="btn-text text-dark-gray fs-16">Bao Bì dạng cuộn</span> 
                                                <span class="btn-icon"><i class="fa-solid fa-arrow-right m-0"></i></span>
                                            </span>
                                        </a> 
                                    </div>
                                    <div class="col md-mb-30px">
                                        <a href="{{ route('cms.store.collections') }}" class="text-center"> 
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-category-02.jpg') }}" alt="">  
                                        </a>
                                        <a href="{{ route('cms.store.collections') }}" class="btn btn-hover-animation text-uppercase-inherit fw-600 ls-0px justify-content-center">
                                            <span> 
                                                <span class="btn-text text-dark-gray fs-16">Thùng - hộp</span> 
                                                <span class="btn-icon"><i class="fa-solid fa-arrow-right m-0"></i></span>
                                            </span>
                                        </a>
                                    </div>
                                    <div class="col md-mb-30px">
                                        <a href="{{ route('cms.store.collections') }}" class="text-center"> 
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-category-03.jpg') }}" alt="">  
                                        </a>
                                        <a href="{{ route('cms.store.collections') }}" class="btn btn-hover-animation text-uppercase-inherit fw-600 ls-0px justify-content-center">
                                            <span> 
                                                <span class="btn-text text-dark-gray fs-16">Dây dai - cuộn</span> 
                                                <span class="btn-icon"><i class="fa-solid fa-arrow-right m-0"></i></span>
                                            </span>
                                        </a> 
                                    </div>
                                    <div class="col md-mb-30px">
                                        <a href="{{ route('cms.store.collections') }}" class="text-center"> 
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-category-04.jpg') }}" alt="">  
                                        </a>
                                        <a href="{{ route('cms.store.collections') }}" class="btn btn-hover-animation text-uppercase-inherit fw-600 ls-0px justify-content-center">
                                            <span> 
                                                <span class="btn-text text-dark-gray fs-16">Chống sốc - Gel</span> 
                                                <span class="btn-icon"><i class="fa-solid fa-arrow-right m-0"></i></span>
                                            </span>
                                        </a> 
                                    </div>
                                    <div class="col md-mb-30px">
                                        <a href="{{ route('cms.store.collections') }}" class="text-center"> 
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-category-05.jpg') }}" alt="">  
                                        </a>
                                        <a href="{{ route('cms.store.collections') }}" class="btn btn-hover-animation text-uppercase-inherit fw-600 ls-0px justify-content-center">
                                            <span> 
                                                <span class="btn-text text-dark-gray fs-16">Bao bì khác</span> 
                                                <span class="btn-icon"><i class="fa-solid fa-arrow-right m-0"></i></span>
                                            </span>
                                        </a>
                                    </div>
                                    <div class="col md-mb-30px">
                                        <a href="{{ route('cms.store.collections') }}" class="text-center"> 
                                            <img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-category-06.jpg') }}" alt="">  
                                        </a>
                                        <a href="{{ route('cms.store.collections') }}" class="btn btn-hover-animation text-uppercase-inherit fw-600 ls-0px justify-content-center">
                                            <span> 
                                                <span class="btn-text text-dark-gray fs-16">Dụng cụ đóng gói</span> 
                                                <span class="btn-icon"><i class="fa-solid fa-arrow-right m-0"></i></span> 
                                            </span>
                                        </a>
                                    </div>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2">
                                    <div class="col">
                                        <a href="{{ route('cms.store.collections') }}"><img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-banner-03.jpg') }}" alt=""></a>
                                    </div>
                                    <div class="col">
                                        <a href="{{ route('cms.store.collections') }}"><img src="{{ asset('modules/cms/assets/images/demo-decor-store-menu-banner-04.jpg') }}" alt=""></a>
                                    </div>
                                </div>
                            </div> 
                        </div>
                    </li>
                    <li class="nav-item dropdown simple-dropdown">
                        <a href="javascript:void(0);" class="nav-link">Pages</a>
                        <i class="fa-solid fa-angle-down dropdown-toggle" id="navbarDropdownMenuLink3" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink3"> 
                            <li><a href="{{ route('cms.about.us') }}">About</a></li>
                            <li><a href="{{ route('cms.store.faq') }}">FAQs</a></li>
                            <li><a href="{{ route('cms.store.wishlist') }}">Wishlist</a></li>
                            <li><a href="{{ route('cms.store.account') }}">Account</a></li>
                            <li><a href="{{ route('cms.store.shop') }}">Shop</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="{{ route('cms.blogs.index') }}" class="nav-link">Blog</a></li>
                    <li class="nav-item"><a href="{{ route('cms.contact.us') }}" class="nav-link">Contact</a></li>
                </ul>
            </div>
        </div>
        <div class="col-auto ms-auto">
            <div class="header-icon">
                <div class="header-search-icon icon">
                    <a href="javascript:void(0)" class="search-form-icon header-search-form"><i class="feather icon-feather-search"></i></a> 
                    <div class="search-form-wrapper">
                        <button title="Close" type="button" class="search-close alt-font">×</button>
                        <form id="search-form" role="search" method="get" class="search-form bg-white" action="#">
                            <div class="search-form-box">
                                <h2 class="text-dark-gray text-center mb-7 alt-font fw-700 ls-minus-1px">What are you looking for?</h2>
                                <input class="search-input alt-font" id="search-form-input5e219ef164995" placeholder="Enter your keywords..." name="s" value="" type="text" autocomplete="off">
                                <button type="submit" class="search-button">
                                    <i class="feather icon-feather-search" aria-hidden="true"></i> 
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="widget-text ms-25px md-ms-20px alt-font">
                    @if(Auth::check())
                        <a href="{{ route('home') }}" class="fs-17 fw-600"><i class="feather icon-feather-user d-inline-block d-xl-none"></i><span class="d-none d-xl-inline-block">Dashboard</span></a>
                    @else
                        <a href="{{ route('login') }}" class="fs-17 fw-600"><i class="feather icon-feather-user d-inline-block d-xl-none"></i><span class="d-none d-xl-inline-block">Login</span></a>
                    @endif
                </div>
            </div>  
        </div>
    </div>
</nav>