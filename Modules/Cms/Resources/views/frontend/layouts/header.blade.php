        <!-- Header -->
        <header class="tf-header header2">
            <div class="header-inner">
                <a href="{{ url('/') }}" class="logo-site">
                    <img src="{{ asset('modules/cms/assets/images/logo/logo.svg') }}" alt="">
                </a>
                <div class="box-navigation">
                    <ul class="nav-menu-main">
                        <li class="menu-item has-child">
                            <a href="{{ url('/') }}" class="item-link link1 active">Home</a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ url('shop/page/about') }}" class="item-link link1">About</a>
                        </li>
                        <li class="menu-item has-child">
                            <a href="{{ url('shop/page/services') }}" class="item-link link1"> @lang('cms::lang.products')</a>
                            <ul class="sub-menu">
                                <li class="sub-menu-item">
                                    <a href="{{ url('shop/products/bao-bi-cuon') }}" class="item-link link1">Bao bi cuon</a>
                                </li>
                                <li class="sub-menu-item">
                                    <a href="{{ url('shop/products/hop-thung-carton') }}" class="item-link link1">Hop thung carton</a>
                                </li>
                            </ul>
                        </li>
                        <li class="menu-item has-child">
                            <a href="{{ url('shop/page/works') }}" class="item-link link1">Works</a>
                            <ul class="sub-menu">
                                <li class="sub-menu-item">
                                    <a href="{{ url('shop/page/works') }}" class="item-link link1">Works</a>
                                </li>
                            </ul>
                        </li>
                        <li class="menu-item has-child">
                            <a href="{{ url('shop/blogs') }}" class="item-link link1">Blog</a>
                            <ul class="sub-menu">
                                <li class="sub-menu-item">
                                    <a href="{{ url('shop/blogs') }}" class="item-link link1">Blog</a>
                                </li>
                            </ul>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('cms.contact.us') }}" class="item-link link1">Contact</a>
                        </li>
                    </ul>
                </div>
                @if(auth()->check())
                    <a href="{{ url('/home') }}" class="tf-btn d-lg-flex d-none">
                        Dashboard
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="tf-btn d-lg-flex d-none">
                        Login
                    </a>
                @endif
                <a href="#" class="tf-btn open-mb-menu mobile-menu d-lg-none d-flex">
                    <i class="icon icon-grip-lines-solid"></i>
                </a>
            </div>
        </header>
        <!-- /Header -->