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
                            <ul class="sub-menu">
                                <li class="sub-menu-item">
                                    <a href="{{ url('/') }}" class="item-link link1">Home</a>
                                </li>
                            </ul>
                        </li>
                        <li class="menu-item">
                            <a href="{{ url('c/page/about') }}" class="item-link link1">About</a>
                        </li>
                        <li class="menu-item has-child">
                            <a href="{{ url('c/page/services') }}" class="item-link link1">Services</a>
                            <ul class="sub-menu">
                                <li class="sub-menu-item">
                                    <a href="{{ url('c/page/services') }}" class="item-link link1">Services</a>
                                </li>
                            </ul>
                        </li>
                        <li class="menu-item has-child">
                            <a href="{{ url('c/page/works') }}" class="item-link link1">Works</a>
                            <ul class="sub-menu">
                                <li class="sub-menu-item">
                                    <a href="{{ url('c/page/works') }}" class="item-link link1">Works</a>
                                </li>
                            </ul>
                        </li>
                        <li class="menu-item has-child">
                            <a href="{{ url('c/blogs') }}" class="item-link link1">Blog</a>
                            <ul class="sub-menu">
                                <li class="sub-menu-item">
                                    <a href="{{ url('c/blogs') }}" class="item-link link1">Blog</a>
                                </li>
                            </ul>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('cms.contact.us') }}" class="item-link link1">Contact</a>
                        </li>
                    </ul>
                </div>
                @if(auth()->check())
                    <a href="{{ url('c/dashboard') }}" class="tf-btn d-lg-flex d-none">
                        Start a Project
                    </a>
                @else
                    <a href="{{ url('c/login') }}" class="tf-btn d-lg-flex d-none">
                        Login
                    </a>
                @endif
                <a href="#" class="tf-btn open-mb-menu mobile-menu d-lg-none d-flex">
                    <i class="icon icon-grip-lines-solid"></i>
                </a>
            </div>
        </header>
        <!-- /Header -->