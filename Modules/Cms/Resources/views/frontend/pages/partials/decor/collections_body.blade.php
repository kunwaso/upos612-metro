        <!-- start page title -->
        <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center position-relative page-title-extra-large">
                        <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">Collections</h1>
                    </div>
                    <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                        <ul>
                            <li><a href="{{ route('cms.home') }}">Home</a></li> 
                            <li>Collections</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->
        <!-- start section -->
        <section class="position-relative">
            <div class="container">
                <div class="row row-cols-1 row-cols-lg-5 row-cols-md-3 row-cols-sm-2 justify-content-center align-items-center" data-anime='{ "el": "childs", "translateY": [50, 0], "translateX": [-50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                    @foreach($featuredCategories as $category)
                        <div class="col categories-style-01 text-center mb-50px xs-mb-35px">
                            <div class="categories-box">
                                <div class="icon-box position-relative mb-10px">
                                    <a href="{{ $category['url'] }}"><img src="{{ asset($category['image_path']) }}" alt="{{ $category['name'] }}"/></a>
                                    <div class="count-circle d-flex align-items-center justify-content-center w-35px h-35px bg-base-color text-white rounded-circle alt-font fw-600 fs-12">{{ $category['count'] }}</div>
                                </div>
                                <a href="{{ $category['url'] }}" class="alt-font fw-600 fs-17 text-dark-gray text-dark-gray-hover">{{ $category['name'] }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <!-- end section -->
