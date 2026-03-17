<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <meta
        name="description"
        content="ProjectX Module - Products & Sales Dashboard"
    />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"
    />
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link
        href="{{ asset('modules/projectx/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}"
        rel="stylesheet"
        type="text/css"
    />
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('modules/projectx/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('modules/projectx/css/style-dash.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
</head>
<!--end::Head-->
<body id="kt_body" class="page-bg">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = 'light';
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute('data-bs-theme-mode')) {
                themeMode = document.documentElement.getAttribute('data-bs-theme-mode');
            } else {
                if (localStorage.getItem('data-bs-theme') !== null) {
                    themeMode = localStorage.getItem('data-bs-theme');
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches
                    ? 'dark'
                    : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Main-->
    <!--begin::Root-->

    @if (session('status'))
        <input type="hidden" id="status_span" data-status="{{ session('status.success') }}" data-msg="{{ session('status.msg') }}">
    @endif

    @yield('content')

    <!--end::Root-->
    <!--end::Main-->
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </div>
    <!--end::Scrolltop-->
    <!--begin::Modals-->

    <!--begin::Modal - Create account-->
    <div class="modal fade" id="kt_modal_create_account" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-fullscreen p-9">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header header-bg">
                    <!--begin::Modal title-->
                    <h2 class="text-white">
                        Create Account
                        <small class="ms-2 fs-7 fw-normal text-white opacity-50"
                            >Become a member and start earning</small
                        >
                    </h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div
                        class="btn btn-sm btn-icon btn-color-white btn-active-color-primary"
                        data-bs-dismiss="modal"
                    >
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body scroll-y m-5">
                    <!--begin::Stepper-->
                    <div
                        class="stepper stepper-links d-flex flex-column"
                        id="kt_create_account_stepper"
                    >
                        <!--begin::Nav-->
                        <div class="stepper-nav py-5">
                            <!--begin::Step 1-->
                            <div class="stepper-item current" data-kt-stepper-element="nav">
                                <h3 class="stepper-title">Account Type</h3>
                            </div>
                            <!--end::Step 1-->
                            <!--begin::Step 2-->
                            <div class="stepper-item" data-kt-stepper-element="nav">
                                <h3 class="stepper-title">Account Info</h3>
                            </div>
                            <!--end::Step 2-->
                            <!--begin::Step 3-->
                            <div class="stepper-item" data-kt-stepper-element="nav">
                                <h3 class="stepper-title">Business Details</h3>
                            </div>
                            <!--end::Step 3-->
                            <!--begin::Step 4-->
                            <div class="stepper-item" data-kt-stepper-element="nav">
                                <h3 class="stepper-title">Billing Details</h3>
                            </div>
                            <!--end::Step 4-->
                            <!--begin::Step 5-->
                            <div class="stepper-item" data-kt-stepper-element="nav">
                                <h3 class="stepper-title">Completed</h3>
                            </div>
                            <!--end::Step 5-->
                        </div>
                        <!--end::Nav-->
                        <!--begin::Form-->
                        <form
                            class="mx-auto mw-600px w-100 py-10"
                            novalidate="novalidate"
                            id="kt_create_account_form"
                        >
                            <!--begin::Step 1-->
                            <div class="current" data-kt-stepper-element="content">
                                <div class="w-100">
                                    <div class="pb-10 pb-lg-15">
                                        <h2 class="fw-bold d-flex align-items-center text-gray-900">
                                            Choose Account Type
                                        </h2>
                                    </div>
                                    <div class="fv-row">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <input type="radio" class="btn-check" name="account_type" value="personal" checked="checked" id="kt_create_account_form_account_type_personal" />
                                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary p-7 d-flex align-items-center mb-10" for="kt_create_account_form_account_type_personal">
                                                    <i class="ki-duotone ki-badge fs-3x me-5">
                                                        <span class="path1"></span><span class="path2"></span>
                                                        <span class="path3"></span><span class="path4"></span>
                                                        <span class="path5"></span>
                                                    </i>
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-4 mb-2">Personal Account</span>
                                                        <span class="text-muted fw-semibold fs-6">If you need more info, please check it out</span>
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-lg-6">
                                                <input type="radio" class="btn-check" name="account_type" value="corporate" id="kt_create_account_form_account_type_corporate" />
                                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary p-7 d-flex align-items-center" for="kt_create_account_form_account_type_corporate">
                                                    <i class="ki-duotone ki-briefcase fs-3x me-5">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-4 mb-2">Corporate Account</span>
                                                        <span class="text-muted fw-semibold fs-6">Create corporate account to manage users</span>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end::Step 1-->
                            <!--begin::Actions-->
                            <div class="d-flex flex-stack pt-15">
                                <div class="mr-2">
                                    <button type="button" class="btn btn-lg btn-light-primary me-3" data-kt-stepper-action="previous">
                                        <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>Back
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">
                                        Continue
                                        <i class="ki-duotone ki-arrow-right fs-4 ms-1 me-0"><span class="path1"></span><span class="path2"></span></i>
                                    </button>
                                </div>
                            </div>
                            <!--end::Actions-->
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Stepper-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Create account-->
    <!--end::Modals-->

    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('modules/projectx/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('modules/projectx/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    <!--begin::Vendors Javascript(used for this page only)-->
    <script src="{{ asset('modules/projectx/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
    <!--end::Vendors Javascript-->
    <!--begin::Custom Javascript(used for this page only)-->
    <script src="{{ asset('modules/projectx/js/custom/utilities/modals/create-account.js') }}"></script>
    <!--end::Custom Javascript-->
    @yield('javascript')
</body>
<!--end::Body-->

</html>
