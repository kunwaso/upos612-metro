<style>
	#kt_app_sidebar_nav_wrapper {
	  height: auto !important;
	}
	</style>
<!--begin::Aside-->
<div id="kt_aside" class="aside px-2" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'275px', '400px': '385px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_toggle">
	
	
	<!--begin::Sidebar nav-->
	<div class="flex-column-fluid px-4 px-lg-8 py-4" id="kt_app_sidebar_nav">
		<!--begin::Nav wrapper-->
		<div
		id="kt_app_sidebar_nav_wrapper"
		class="d-flex flex-column hover-scroll-y pe-4 me-n4"
		data-kt-scroll="true"
		data-kt-scroll-activate="true"
		data-kt-scroll-height="auto"
		data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
		data-kt-scroll-wrappers="#kt_app_sidebar, #kt_app_sidebar_nav"
		data-kt-scroll-offset="5px"
		>
			<!--begin::Back to Home-->
			<div class="aside-footer flex-column-auto px-4 pt-3 pb-7" id="kt_aside_footer">
				<a href="{{ route('home') }}" class="btn btn-custom btn-danger w-100" data-bs-toggle="tooltip" data-bs-trigger="hover" title="{{ __('projectx::lang.exit_projectx') }}">
					<span class="btn-label">{{ __('projectx::lang.back_to_home') }}</span>
					<i class="ki-duotone ki-entrance-right btn-icon fs-2 text-white">
						<span class="path1"></span>
						<span class="path2"></span>
					   </i>
				</a>
			</div>
			<!--end::Back to Home-->

		<!--begin::Progress-->
		<div class="d-flex align-items-center flex-column w-100 mb-6">
			<div class="d-flex justify-content-between fw-bolder fs-6 text-gray-800 w-100 mt-auto mb-3">
			<span>Your Goal</span>
			</div>
	
			<div class="w-100 bg-light-primary rounded mb-2" style="height: 24px">
			<div
				class="bg-primary rounded"
				role="progressbar"
				style="height: 24px; width: 37%"
				aria-valuenow="50"
				aria-valuemin="0"
				aria-valuemax="100"
			></div>
			</div>
	
			<div class="fw-semibold fs-7 text-primary w-100 mt-auto">
			<span>reached 37% of your target</span>
			</div>
		</div>
		<!--end::Progress-->
	
		<!--begin::Stats-->
		<div class="d-flex mb-3 mb-lg-6">
			<!--begin::Stat-->
			<div class="border border-gray-300 border-dashed rounded min-w-100px w-100 py-2 px-4 me-6">
			<!--begin::Date-->
			<span class="fs-6 text-gray-500 fw-bold">Budget</span>
			<!--end::Date-->
	
			<!--begin::Label-->
			<div class="fs-2 fw-bold text-success">$14,350</div>
			<!--end::Label-->
			</div>
			<!--end::Stat-->
	
			<!--begin::Stat-->
			<div class="border border-gray-300 border-dashed rounded min-w-100px w-100 py-2 px-4">
			<!--begin::Date-->
			<span class="fs-6 text-gray-500 fw-bold">Spent</span>
			<!--end::Date-->
	
			<!--begin::Label-->
			<div class="fs-2 fw-bold text-danger">$8,029</div>
			<!--end::Label-->
			</div>
			<!--end::Stat-->
		</div>
		<!--end::Stats-->
	
		<!--begin::Links-->
		<div class="mb-6">
			<!--begin::Title-->
			<h3 class="text-gray-800 fw-bold mb-8">{{ __('projectx::lang.X-Projects') }}</h3>
			<!--end::Title-->
	
			<!--begin::Row-->
			<div class="row row-cols-3" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button]">
			
			<!--begin::fabrics-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="{{ route('projectx.fabric_manager.list') }}"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200 hover-elevate-up"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-duotone ki-mouse-square fs-1 text-info">
						<span class="path1"></span>
						<span class="path2"></span>
					   </i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">{{ __('projectx::lang.fabrics') }}</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::fabrics-->

			@can('projectx.trim.view')
			<!--begin::trim-manager-->
			<div class="col mb-4">
				<a
				href="{{ route('projectx.trim_manager.list') }}"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<span class="mb-2">
					<i class="ki-duotone ki-tag fs-1 text-primary">
						<span class="path1"></span>
						<span class="path2"></span>
						<span class="path3"></span>
					</i>
				</span>
				<span class="fs-7 fw-bold">{{ __('projectx::lang.trim_manager') }}</span>
				</a>
			</div>
			<!--end::trim-manager-->
			@endcan
	
			<!--begin::sales-quote-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="{{ route('projectx.sales.orders.index') }}"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-duotone ki-shop fs-1 text-warning">
						<span class="path1"></span>
						<span class="path2"></span>
						<span class="path3"></span>
						<span class="path4"></span>
						<span class="path5"></span>
					   </i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">{{ __('projectx::lang.sales_orders') }}</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::sales-quote-->
	
			<!--begin::Quotes-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="{{ route('projectx.sales') }}"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-duotone ki-book fs-1 text-success">
					<span class="path1"></span>
					<span class="path2"></span>
					<span class="path3"></span>
					<span class="path4"></span>
				   </i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">{{ __('projectx::lang.quotes') }}</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::Col-->
	
			<!--begin::contacts-->
			@if(auth()->user()->can('supplier.view') || auth()->user()->can('customer.view') || auth()->user()->can('customer.view_own') || auth()->user()->can('supplier.view_own'))
			<div class="col mb-4">
				<a
				href="{{ route('projectx.contacts.index') }}"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<span class="mb-2">
					<i class="ki-duotone ki-address-book fs-1 text-info">
						<span class="path1"></span>
						<span class="path2"></span>
						<span class="path3"></span>
					</i>
				</span>
				<span class="fs-7 fw-bold">{{ __('contact.contacts') }}</span>
				</a>
			</div>
			@endif
			<!--end::contacts-->

			<!--begin::user-profile-->
			<div class="col mb-4">
				<a
				href="{{ route('projectx.user_profile.index') }}"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<span class="mb-2">
					<i class="ki-duotone ki-address-book fs-1 text-dark">
						<span class="path1"></span>
						<span class="path2"></span>
						<span class="path3"></span>
					</i>
				</span>
				<span class="fs-7 fw-bold">{{ __('projectx::lang.user_profile') }}</span>
				</a>
			</div>
			<!--end::user-profile-->

			<!--begin::chat-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="{{ route('projectx.chat.index') }}"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-duotone ki-messages fs-1 text-primary">
						<span class="path1"></span>
						<span class="path2"></span>
						<span class="path3"></span>
						<span class="path4"></span>
						<span class="path5"></span>
					   </i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">{{ __('projectx::lang.ai_assistant') }}</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::chat-->

			@if(auth()->user()->can('projectx.site_manager.edit'))
				<div class="col mb-4">
					<a
					href="{{ route('projectx.site_manager.index') }}"
					class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
					data-kt-button="true"
					>
						<span class="mb-2">
							<i class="ki-duotone ki-home fs-1 text-success">
								<span class="path1"></span>
								<span class="path2"></span>
							</i>
						</span>
						<span class="fs-7 fw-bold">{{ __('projectx::lang.site_manager') }}</span>
					</a>
				</div>
			@endif
				<!--begin::Col-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="/metronic8/demo23/?page=apps/calendar"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-outline ki-calendar fs-1"></i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">{{ __('projectx::lang.calendar') }}</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::Col-->

			<!--begin::Col-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="/metronic8/demo23/?page=apps/contacts/getting-started"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-outline ki-rocket fs-1"></i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">CareCal</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::Col-->
	
			<!--begin::Col-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="/metronic8/demo23/?page=apps/projects/list"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-outline ki-geolocation fs-1"></i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">Hospitality</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::Col-->
	
			<!--begin::Col-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="/metronic8/demo23/?page=apps/file-manager/folders"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px border-gray-200"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-outline ki-abstract-28 fs-1"></i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">Utilities</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::Col-->
	
			<!--begin::Col-->
			<div class="col mb-4">
				<!--begin::Link-->
				<a
				href="/metronic8/demo23/?page=apps/contacts/add-contact"
				class="btn btn-icon btn-outline btn-bg-light btn-active-light-primary btn-flex flex-column flex-center w-lg-90px h-lg-90px w-70px h-70px active border-primary border-dashed"
				data-kt-button="true"
				>
				<!--begin::Icon-->
				<span class="mb-2">
					<i class="ki-outline ki-plus fs-1"></i>
				</span>
				<!--end::Icon-->
	
				<!--begin::Label-->
				<span class="fs-7 fw-bold">Add New</span>
				<!--end::Label-->
				</a>
				<!--end::Link-->
			</div>
			<!--end::Col-->
			</div>
			<!--end::Row-->
		</div>
		<!--end::Links-->
		</div>
		<!--end::Nav wrapper-->
	</div>
	<!--end::Sidebar nav-->

	<!--begin::Aside menu-->
	<div class="aside-menu flex-column-fluid">
		<div class="hover-scroll-overlay-y my-5 mx-2" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_aside_footer" data-kt-scroll-wrappers="#kt_aside, #kt_aside_menu" data-kt-scroll-offset="2px">
			<!--begin::Menu-->
			<div class="menu menu-column menu-sub-indention menu-active-bg menu-state-primary menu-title-gray-700 fs-6 menu-rounded w-100 fw-semibold" id="kt_aside_menu" data-kt-menu="true">

				{{-- Main Pages --}}
				<div class="menu-item pt-5">
					<div class="menu-content">
						<span class="menu-heading fw-bold text-uppercase fs-7">Main Pages</span>
					</div>
				</div>

				<div class="menu-item {{ request()->routeIs('home') ? 'here show' : '' }}">
					<div class="menu-item">
						<a class="menu-link" href="{{ route('home') }}">
							<span class="menu-icon">
								<i class="ki-duotone ki-element-11 fs-2">
									<span class="path1"></span>
									<span class="path2"></span>
									<span class="path3"></span>
									<span class="path4"></span>
								</i>
							</span>
							<span class="menu-title">Home</span>
						</a>
					</div>
				</div>

				<div class="menu-item {{ request()->routeIs('products.*') ? 'here show' : '' }}">
					<div class="menu-item">
						<a class="menu-link" href="{{ route('products.index') }}">
							<span class="menu-icon">
								<i class="ki-duotone ki-color-swatch fs-2">
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
									<span class="path11"></span>
									<span class="path12"></span>
									<span class="path13"></span>
									<span class="path14"></span>
									<span class="path15"></span>
									<span class="path16"></span>
									<span class="path17"></span>
									<span class="path18"></span>
									<span class="path19"></span>
									<span class="path20"></span>
									<span class="path21"></span>
								</i>
							</span>
							<span class="menu-title">Products</span>
						</a>
					</div>
				</div>

				<div class="menu-item {{ request()->routeIs('sells.*') ? 'here show' : '' }}">
					<div class="menu-item">
						<a class="menu-link" href="{{ route('sells.index') }}">
							<span class="menu-icon">
								<i class="ki-duotone ki-basket fs-2">
									<span class="path1"></span>
									<span class="path2"></span>
									<span class="path3"></span>
									<span class="path4"></span>
								</i>
							</span>
							<span class="menu-title">Sales</span>
						</a>
					</div>
				</div>

				<div class="menu-item {{ request()->routeIs('purchases.*') ? 'here show' : '' }}">
					<div class="menu-item">
						<a class="menu-link" href="{{ route('purchases.index') }}">
							<span class="menu-icon">
								<i class="ki-duotone ki-document fs-2">
									<span class="path1"></span>
									<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">Purchase Management</span>
						</a>
					</div>
				</div>

				{{-- Apps — ProjectX --}}
				<div class="menu-item pt-5">
					<div class="menu-content">
						<span class="menu-heading fw-bold text-uppercase fs-7">Apps</span>
					</div>
				</div>

				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('projectx.fabric_manager.*') ? 'here show' : '' }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="ki-duotone ki-abstract-26 fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
							</i>
						</span>
						<span class="menu-title">{{ __('projectx::lang.fabric_manager') }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('projectx.fabric_manager.list') ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.list') }}">
								<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
								<span class="menu-title">{{ __('projectx::lang.my_fabrics') }}</span>
							</a>
						</div>
					</div>
				</div>

				@can('projectx.trim.view')
					<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('projectx.trim_manager.*') ? 'here show' : '' }}">
						<span class="menu-link">
							<span class="menu-icon">
								<i class="ki-duotone ki-tag fs-2">
									<span class="path1"></span>
									<span class="path2"></span>
									<span class="path3"></span>
								</i>
							</span>
							<span class="menu-title">{{ __('projectx::lang.trim_manager') }}</span>
							<span class="menu-arrow"></span>
						</span>
						<div class="menu-sub menu-sub-accordion">
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.trim_manager.list') ? 'active' : '' }}" href="{{ route('projectx.trim_manager.list') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('projectx::lang.trims_and_accessories') }}</span>
								</a>
							</div>
						</div>
				</div>
			@endcan

				<div class="menu-item {{ request()->routeIs('projectx.user_profile.*') ? 'here show' : '' }}">
					<a class="menu-link {{ request()->routeIs('projectx.user_profile.*') ? 'active' : '' }}" href="{{ route('projectx.user_profile.index') }}">
						<span class="menu-icon">
							<i class="ki-duotone ki-address-book fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								<span class="path3"></span>
							</i>
						</span>
						<span class="menu-title">{{ __('projectx::lang.user_profile') }}</span>
					</a>
				</div>

				@if((bool) app(\App\Utils\ModuleUtil::class)->hasThePermissionInSubscription((int) session('user.business_id'), 'essentials_module'))
					<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('projectx.essentials.*') ? 'here show' : '' }}">
						<span class="menu-link">
							<span class="menu-icon">
								<i class="ki-duotone ki-check-square fs-2">
									<span class="path1"></span>
									<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">{{ __('essentials::lang.essentials') }}</span>
							<span class="menu-arrow"></span>
						</span>
						<div class="menu-sub menu-sub-accordion">
                            @php
                                $projectx_essentials_is_admin = app(\App\Utils\ModuleUtil::class)->is_admin(auth()->user(), (int) session('user.business_id'));
                            @endphp
							@if(auth()->user()->can('essentials.add_todos') || auth()->user()->can('essentials.edit_todos') || auth()->user()->can('essentials.delete_todos') || auth()->user()->can('essentials.assign_todos'))
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('projectx.essentials.todo.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.todo.index') }}">
										<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
										<span class="menu-title">{{ __('essentials::lang.todo') }}</span>
									</a>
								</div>
							@endif
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.essentials.documents.*') && request('type', 'document') === 'document' ? 'active' : '' }}" href="{{ route('projectx.essentials.documents.index', ['type' => 'document']) }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('essentials::lang.document') }}</span>
								</a>
							</div>
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.essentials.documents.*') && request('type') === 'memos' ? 'active' : '' }}" href="{{ route('projectx.essentials.documents.index', ['type' => 'memos']) }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('essentials::lang.memos') }}</span>
								</a>
							</div>
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.essentials.reminders.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.reminders.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('essentials::lang.reminders') }}</span>
								</a>
							</div>
							@if(auth()->user()->can('essentials.view_message') || auth()->user()->can('essentials.create_message'))
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('projectx.essentials.messages.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.messages.index') }}">
										<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
										<span class="menu-title">{{ __('essentials::lang.messages') }}</span>
									</a>
								</div>
							@endif
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.essentials.knowledge-base.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.knowledge-base.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('essentials::lang.knowledge_base') }}</span>
								</a>
							</div>
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.dashboard') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.dashboard') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">{{ __('essentials::lang.hrm_dashboard') }}</span>
                                </a>
                            </div>
                            @if(auth()->user()->can('essentials.crud_all_leave') || auth()->user()->can('essentials.crud_own_leave'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.leave.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.leave.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('essentials::lang.leave') }}</span>
                                    </a>
                                </div>
                            @endif
                            @if(auth()->user()->can('essentials.crud_leave_type'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.leave-type.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.leave-type.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('essentials::lang.leave_type') }}</span>
                                    </a>
                                </div>
                            @endif
                            @if(auth()->user()->can('essentials.crud_all_attendance') || auth()->user()->can('essentials.view_own_attendance'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.attendance.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.attendance.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('essentials::lang.attendance') }}</span>
                                    </a>
                                </div>
                            @endif
                            @if($projectx_essentials_is_admin || auth()->user()->can('superadmin'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.shift.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.shift.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('essentials::lang.shifts') }}</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.holiday.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.holiday.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('essentials::lang.holiday') }}</span>
                                    </a>
                                </div>
                            @endif
                            @if(auth()->user()->can('essentials.view_allowance_and_deduction') || auth()->user()->can('essentials.add_allowance_and_deduction'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('projectx.essentials.allowance-deduction.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.allowance-deduction.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('essentials::lang.pay_components') }}</span>
                                    </a>
                                </div>
                            @endif
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.payroll.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.payroll.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">{{ __('essentials::lang.payroll') }}</span>
                                </a>
                            </div>
                            @if(auth()->user()->can('essentials.access_sales_target'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('projectx.essentials.hrm.sales-target.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.hrm.sales-target.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">{{ __('essentials::lang.sales_target') }}</span>
                                    </a>
                                </div>
                            @endif
							@if(auth()->user()->can('edit_essentials_settings'))
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('projectx.essentials.settings.*') ? 'active' : '' }}" href="{{ route('projectx.essentials.settings.edit') }}">
										<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
										<span class="menu-title">{{ __('business.settings') }}</span>
									</a>
								</div>
							@endif
						</div>
					</div>
				@endif

				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('projectx.sales', 'projectx.quotes.*', 'projectx.sales.orders.*', 'projectx.settings.quotes.*') ? 'here show' : '' }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="ki-duotone ki-basket fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								<span class="path3"></span>
								<span class="path4"></span>
							</i>
						</span>
						<span class="menu-title">{{ __('projectx::lang.fabric_quotes') }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('projectx.sales', 'projectx.quotes.*') ? 'active' : '' }}" href="{{ route('projectx.sales') }}">
								<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
								<span class="menu-title">{{ __('projectx::lang.quotes') }}</span>
							</a>
						</div>
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('projectx.sales.orders.*') ? 'active' : '' }}" href="{{ route('projectx.sales.orders.index') }}">
								<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
								<span class="menu-title">{{ __('projectx::lang.sales_orders') }}</span>
							</a>
						</div>
						@if(auth()->user()->can('projectx.quote.edit'))
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.settings.quotes.*') ? 'active' : '' }}" href="{{ route('projectx.settings.quotes.edit') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('projectx::lang.quote_settings') }}</span>
								</a>
							</div>
						@endif
					</div>
				</div>

				@if(auth()->user()->can('projectx.chat.view') && !empty($aiChatConfig) && !empty($aiChatConfig['enabled']))
					<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('projectx.chat.*') ? 'here show' : '' }}">
						<span class="menu-link">
							<span class="menu-icon">
								<i class="ki-duotone ki-message-text-2 fs-2">
									<span class="path1"></span>
									<span class="path2"></span>
									<span class="path3"></span>
								</i>
							</span>
							<span class="menu-title">{{ __('projectx::lang.ai_assistant') }}</span>
							<span class="menu-arrow"></span>
						</span>
						<div class="menu-sub menu-sub-accordion">
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.chat.index') ? 'active' : '' }}" href="{{ route('projectx.chat.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('projectx::lang.ai_chat') }}</span>
								</a>
							</div>
							@if(auth()->user()->can('projectx.chat.settings'))
								<div class="menu-item">
									<a class="menu-link {{ request()->routeIs('projectx.chat.settings') ? 'active' : '' }}" href="{{ route('projectx.chat.settings') }}">
										<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
										<span class="menu-title">{{ __('projectx::lang.chat_settings') }}</span>
									</a>
								</div>
							@endif
						</div>
					</div>
				@endif

				@can('projectx.site_manager.edit')
					<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('projectx.site_manager.*') ? 'here show' : '' }}">
						<span class="menu-link">
							<span class="menu-icon">
								<i class="ki-duotone ki-home fs-2">
									<span class="path1"></span>
									<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">{{ __('projectx::lang.site_manager') }}</span>
							<span class="menu-arrow"></span>
						</span>
						<div class="menu-sub menu-sub-accordion">
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.site_manager.index') ? 'active' : '' }}" href="{{ route('projectx.site_manager.index') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('projectx::lang.site_manager') }}</span>
								</a>
							</div>
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('projectx.site_manager.edit') ? 'active' : '' }}" href="{{ route('projectx.site_manager.edit') }}">
									<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
									<span class="menu-title">{{ __('general.edit') }}</span>
								</a>
							</div>
						</div>
					</div>
				@endcan

			</div>
			<!--end::Menu-->
		</div>
	</div>
	<!--end::Aside menu-->

</div>
<!--end::Aside-->
