        <!-- start page title -->
        <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center position-relative page-title-extra-large">
                        <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">My account</h1>
                    </div>
                    <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                        <ul>
                            <li><a href="{{ route('cms.home') }}">Home</a></li> 
                            <li>My account</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title --> 
        <!-- start section -->
        <section>
            <div class="container">
                <div class="row g-0 justify-content-center">
                    <div class="col-xl-4 col-lg-5 col-md-10 contact-form-style-04 md-mb-50px" data-anime='{ "translateY": [0, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                        <span class="fs-26 xs-fs-24 alt-font fw-600 text-dark-gray mb-20px d-block">Member login</span>
                        <form action="https://craftohtml.themezaa.com/email-templates/contact-form.php" method="post">
                            <label class="text-dark-gray mb-10px fw-500">Username or email address<span class="text-red">*</span></label> 
                            <input class="mb-20px bg-very-light-gray form-control required" type="email" name="name" placeholder="Enter your username" />
                            <label class="text-dark-gray mb-10px fw-500">Password<span class="text-red">*</span></label> 
                            <input class="mb-20px bg-very-light-gray form-control required" type="password" name="password" placeholder="Enter your password" />
                            <div class="position-relative terms-condition-box text-start d-flex align-items-center mb-20px">
                                <label>
                                    <input type="checkbox" name="terms_condition" id="terms_condition" value="1" class="terms-condition check-box align-middle required">
                                    <span class="box fs-14">Remember me</span>
                                </label>
                                <a href="#" class="fs-14 text-dark-gray fw-500 text-decoration-line-bottom ms-auto">Forget your password?</a>
                            </div>
                            <input type="hidden" name="redirect" value="">
                            <button class="btn btn-medium btn-round-edge btn-base-color btn-box-shadow submit w-100 text-transform-none" type="submit">Login</button> 
                            <div class="form-results mt-20px d-none"></div>
                        </form>
                    </div>
                    <div class="col-lg-6 col-md-10 offset-xl-2 offset-lg-1 p-6 box-shadow-extra-large border-radius-6px" data-anime='{ "translateY": [0, 0], "opacity": [0,1], "duration": 600, "delay":150, "staggervalue": 150, "easing": "easeOutQuad" }'>
                        <span class="fs-26 xs-fs-24 alt-font fw-600 text-dark-gray mb-20px d-block">Create an account</span>
                        <form action="https://craftohtml.themezaa.com/email-templates/contact-form.php" method="post">
                            <label class="text-dark-gray mb-10px fw-500">Username<span class="text-red">*</span></label> 
                            <input class="mb-20px bg-very-light-gray form-control required" type="text" name="name" placeholder="Enter your username" />
                            <label class="text-dark-gray mb-10px fw-500">Email address<span class="text-red">*</span></label> 
                            <input class="mb-20px bg-very-light-gray form-control required" type="email" name="email" placeholder="Enter your email" />
                            <label class="text-dark-gray mb-10px fw-500">Password<span class="text-red">*</span></label> 
                            <input class="mb-20px bg-very-light-gray form-control required" type="password" name="password" placeholder="Enter your password" /> 
                            <span class="fs-13 lh-22 w-90 lg-w-100 md-w-90 sm-w-100 d-block mb-30px">Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our <a href="#" class="text-dark-gray text-decoration-line-bottom fw-500">privacy policy.</a></span>
                            <input type="hidden" name="redirect" value="">
                            <button class="btn btn-medium btn-round-edge btn-base-color btn-box-shadow submit w-100 text-transform-none" type="submit">Register</button>
                            <div class="form-results mt-20px d-none"></div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section --> 
