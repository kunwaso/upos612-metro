<div id="kt_header" class="header" data-kt-sticky="true" data-kt-sticky-name="header" data-kt-sticky-animation="false" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
    <!--begin::Container-->
    <div class="container-xxl d-flex align-items-center flex-lg-stack">
        <!--begin::Brand-->
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-2 me-lg-5">
            <!--begin::Wrapper-->
            <div class="flex-grow-1">
                <!--begin::Aside toggle-->
                <button class="btn btn-icon btn-color-gray-800 btn-active-color-primary ms-n4 me-lg-12" id="kt_aside_toggle">
                    <i class="ki-duotone ki-abstract-14 fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <!--end::Aside toggle-->
                <!--begin::Header Logo-->
                <a href="{{ url('/') }}">
                    <img alt="Logo" src="{{ asset('assets/media/logos/default-small.svg') }}" class="h-30px" />
                </a>
                <!--end::Header Logo-->
            </div>
            <!--end::Wrapper-->
            <!--begin:Search-->
            <div class="ms-5 ms-md-17 d-flex align-items-center">
                <div id="kt_header_global_search" class="header-search d-flex align-items-center w-lg-400px" data-kt-search-keypress="true" data-kt-search-min-length="2" data-kt-search-enter="enter" data-kt-search-layout="menu" data-kt-search-responsive="lg" data-kt-menu-trigger="auto" data-kt-menu-permanent="true" data-kt-menu-placement="{default: 'bottom-end', lg: 'bottom-start'}">
                    <div data-kt-search-element="toggle" class="search-toggle-mobile d-flex d-lg-none align-items-center">
                        <div class="d-flex btn btn-icon btn-color-gray-800 btn-active-light-primary w-30px h-30px w-md-40px h-md-40px">
                            <i class="ki-duotone ki-magnifier fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>

                    <form data-kt-search-element="form" class="d-none d-lg-block w-100 position-relative mb-5 mb-lg-0" autocomplete="off">
                        <input type="hidden" />
                        <i class="ki-duotone ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input
                            type="text"
                            class="search-input form-control form-control-solid ps-13"
                            name="search"
                            value=""
                            placeholder="Search records..."
                            data-kt-search-element="input"
                            @if(empty($globalSearchConfig['types'])) disabled @endif />
                        <span class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-5" data-kt-search-element="spinner">
                            <span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
                        </span>
                        <span class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-4" data-kt-search-element="clear">
                            <i class="ki-duotone ki-cross fs-2 fs-lg-1 me-0">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                    </form>

                    <div data-kt-search-element="content" class="menu menu-sub menu-sub-dropdown py-5 px-5 overflow-hidden w-350px w-md-425px">
                        <div data-kt-search-element="wrapper">
                            @if(!empty($globalSearchConfig['types']))
                                <div class="d-flex flex-wrap gap-2 mb-4" data-global-search-element="type-selector">
                                    @foreach($globalSearchConfig['types'] as $typeKey => $typeConfig)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light-primary"
                                            data-search-type="{{ $typeKey }}"
                                            aria-pressed="{{ ($globalSearchConfig['defaultType'] ?? null) === $typeKey ? 'true' : 'false' }}">
                                            {{ $typeConfig['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <div data-kt-search-element="suggestions">
                                <div class="rounded border border-dashed border-gray-300 p-5">
                                    <div class="d-flex align-items-start">
                                        <div class="symbol symbol-40px me-4">
                                            <span class="symbol-label bg-light-primary text-primary">
                                                <i class="ki-duotone ki-magnifier fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fs-6 fw-bold text-gray-900 mb-1">Search related content</div>
                                            <div class="text-muted fw-semibold fs-7 mb-4">
                                                Search live records from your current business directly from the header.
                                            </div>
                                            @if(!empty($globalSearchConfig['types']))
                                                <div class="d-flex flex-wrap gap-2 mb-4">
                                                    @foreach($globalSearchConfig['types'] as $typeConfig)
                                                        <span class="badge badge-light">{{ $typeConfig['label'] }}</span>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_users_search">
                                                    Open full search
                                                </button>
                                            @else
                                                <div class="text-muted fw-semibold fs-7">No search types are available for your account.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div data-kt-search-element="results" class="d-none">
                                <div class="scroll-y mh-250px mh-lg-350px" data-global-search-element="results-list"></div>
                            </div>

                            <div data-global-search-element="error" class="text-center d-none py-10">
                                <div class="fs-5 fw-bold text-gray-900 mb-2">Search failed</div>
                                <div class="text-muted fw-semibold fs-7">Please try again in a moment.</div>
                            </div>

                            <div data-kt-search-element="empty" class="text-center d-none py-10">
                                <div class="pt-2 pb-5">
                                    <i class="ki-duotone ki-search-list fs-3x opacity-50">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                                <div class="fw-semibold">
                                    <h3 class="text-gray-600 fs-5 mb-2">No results found</h3>
                                    <div class="text-muted fs-7">Try another keyword or switch to a different search type.</div>
                                </div>
                            </div>
                        </div>

                        <template id="kt_header_global_search_result_template">
                            <a href="#" class="d-flex align-items-center justify-content-between rounded px-4 py-3 mb-2 text-gray-900 text-hover-primary bg-state-light bg-state-opacity-50">
                                <div class="min-w-0 pe-4">
                                    <div class="fs-6 fw-bold text-truncate" data-global-search-field="text"></div>
                                    <div class="text-muted fw-semibold fs-7 text-truncate" data-global-search-field="subtitle"></div>
                                </div>
                                <span class="badge badge-light-primary flex-shrink-0" data-global-search-field="type-label"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
            <!--end:Search-->
        </div>
        <!--end::Brand-->
        <!--begin::Toolbar wrapper-->
        <div class="d-flex align-items-stretch flex-shrink-0">
            <!--begin::Button-->
            <a href="#" class="btn btn-light-success me-1" data-bs-toggle="modal" data-bs-target="#kt_modal_create_project">Create</a>
            <!--end::Button-->
            @can('profit_loss_report.view')
                <button type="button" id="view_todays_profit" class="btn btn-light-primary me-1" data-bs-toggle="modal" data-bs-target="#todays_profit_modal" title="{{ __('home.todays_profit') }}">
                    <i class="ki-duotone ki-chart-line-up fs-3 me-0">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="d-none d-lg-inline">@lang('home.todays_profit')</span>
                </button>
            @endcan
            <a href="{{ route('calendar') }}" class="btn btn-light-info me-1" title="{{ __('lang_v1.calendar') }}">
                <i class="ki-duotone ki-calendar-8 fs-3 me-0">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                    <span class="path6"></span>
                </i>
                <span class="d-none d-lg-inline">@lang('lang_v1.calendar')</span>
            </a>
            @if(!empty($aiChatConfig) && !empty($aiChatConfig['enabled']))
                <!--begin::Activities-->
                <div class="d-flex align-items-center ms-1 ms-lg-3">
                    <!--begin::drawer toggle-->
                    <div class="position-relative btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" id="kt_drawer_chat_toggle">
                        <i class="ki-duotone ki-notification-status fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                        </i>
                        <span class="bullet bullet-dot bg-danger h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink"></span>
                    </div>
                    <!--end::drawer toggle-->
                </div>
                <!--end::Activities-->
            @endif
            <!--begin::Theme mode-->
            <div class="d-flex align-items-center ms-1 ms-lg-3">
                <!--begin::Menu toggle-->
                <a href="#" class="btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-night-day theme-light-show fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                        <span class="path6"></span>
                        <span class="path7"></span>
                        <span class="path8"></span>
                        <span class="path9"></span>
                        <span class="path10"></span>
                    </i>
                    <i class="ki-duotone ki-moon theme-dark-show fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </a>
                <!--begin::Menu toggle-->
                <!--begin::Menu-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                            <span class="menu-icon" data-kt-element="icon">
                                <i class="ki-duotone ki-night-day fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                    <span class="path6"></span>
                                    <span class="path7"></span>
                                    <span class="path8"></span>
                                    <span class="path9"></span>
                                    <span class="path10"></span>
                                </i>
                            </span>
                            <span class="menu-title">Light</span>
                        </a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                            <span class="menu-icon" data-kt-element="icon">
                                <i class="ki-duotone ki-moon fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Dark</span>
                        </a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                            <span class="menu-icon" data-kt-element="icon">
                                <i class="ki-duotone ki-screen fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">System</span>
                        </a>
                    </div>
                    <!--end::Menu item-->
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Theme mode-->
            <!--begin::User menu-->
            <div class="d-flex align-items-center ms-1 ms-lg-3">
                <!--begin::Menu wrapper-->
                <div class="btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px position-relative btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-user fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
                <!--begin::User account menu-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <div class="menu-content d-flex align-items-center px-3">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-50px me-5">
                                <img alt="Logo" src="{{ auth()->user()->image_url }}" />
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Username-->
                            <div class="d-flex flex-column">
                                <div class="fw-bold d-flex align-items-center fs-5">{{ trim(auth()->user()->user_full_name) }}
                                    @if(!empty(auth()->user()->role_name))
                                        <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">{{ auth()->user()->role_name }}</span>
                                    @endif
                                </div>
                                <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
                            </div>
                            <!--end::Username-->
                        </div>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu separator-->
                    <div class="separator my-2"></div>
                    <!--end::Menu separator-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-5">
                        <a href="{{ action([\App\Http\Controllers\UserController::class, 'getProfile']) }}" class="menu-link px-5">@lang('lang_v1.my_profile')</a>
                    </div>
                    <!--end::Menu item-->
                    @can('business_settings.access')
                        <!--begin::Menu item-->
                        <div class="menu-item px-5 my-1">
                            <a href="{{ action([\App\Http\Controllers\BusinessController::class, 'getBusinessSettings']) }}" class="menu-link px-5">@lang('business.business_settings')</a>
                        </div>
                        <!--end::Menu item-->
                    @endcan
                    <!--begin::Menu separator-->
                    <div class="separator my-2"></div>
                    <!--end::Menu separator-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-5">
                        <a href="{{ action([\App\Http\Controllers\Auth\LoginController::class, 'logout']) }}" class="menu-link px-5">@lang('lang_v1.sign_out')</a>
                    </div>
                    <!--end::Menu item-->
                </div>
                <!--end::User account menu-->
                <!--end::Menu wrapper-->
            </div>
            <!--end::User menu-->
            @if(!empty($aiChatConfig) && !empty($aiChatConfig['enabled']))
                <!--begin::Chat-->
                <div class="d-flex align-items-center ms-1 ms-lg-3">
                    <!--begin::Drawer wrapper-->
                    <div class="btn btn-icon btn-danger position-relative w-30px h-30px w-md-40px h-md-40px" id="kt_drawer_chat_toggle">3</div>
                    <!--end::Drawer wrapper-->
                </div>
                <!--end::Chat-->
            @endif
        </div>
        <!--end::Toolbar wrapper-->
    </div>
    <!--end::Container-->
</div>
