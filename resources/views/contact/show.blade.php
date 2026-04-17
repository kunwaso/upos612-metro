@extends('layouts.app')
@section('title', __('contact.view_contact'))

@section('content')

{{-- Hidden inputs for JS --}}
<input type="hidden" id="sell_list_filter_customer_id" value="{{ $contact->id }}">
<input type="hidden" id="purchase_list_filter_supplier_id" value="{{ $contact->id }}">
@php
    $can_edit_contact = auth()->user()->can('supplier.update') || auth()->user()->can('customer.update');
@endphp

{{-- Toolbar --}}
<div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">@lang('contact.view_contact')</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a>
                </li>
                <li class="breadcrumb-item text-gray-600">
                    @if ($is_supplier)
                        <a href="{{ route('contacts.index', ['type' => 'supplier']) }}" class="text-gray-600 text-hover-primary">@lang('lang_v1.suppliers')</a>
                    @else
                        <a href="{{ route('contacts.index', ['type' => 'customer']) }}" class="text-gray-600 text-hover-primary">@lang('lang_v1.customers')</a>
                    @endif
                </li>
                <li class="breadcrumb-item text-gray-500">{{ $contact->name }}</li>
            </ul>
        </div>
        <div class="d-flex align-items-center py-2">
            {!! Form::select('contact_id', $contact_dropdown, $contact->id, ['class' => 'form-select form-select-solid w-200px', 'id' => 'contact_id']) !!}
        </div>
    </div>
</div>

{{-- Main content --}}
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">

        {{-- Hero Navbar card (overview.html pattern) --}}
        <div class="card mb-5 mb-xl-10">
            <div class="card-body pt-9 pb-0">
                {{-- Details row --}}
                <div class="d-flex flex-wrap flex-sm-nowrap">
                    {{-- Avatar --}}
                    <div class="me-7 mb-4">
                        <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                            @if (!empty($contact->image))
                                <img src="{{ asset('uploads/img/' . $contact->image) }}" alt="{{ $contact->name }}">
                            @else
                                <div class="symbol-label fs-1 fw-bold text-primary bg-light-primary">
                                    {{ mb_strtoupper(mb_substr($contact->name ?? 'C', 0, 1)) }}
                                </div>
                            @endif
                            <div class="position-absolute translate-middle bottom-0 start-100 mb-6 rounded-circle border border-4 border-body h-20px w-20px
                                {{ $contact->contact_status === 'active' ? 'bg-success' : 'bg-danger' }}">
                            </div>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="flex-grow-1">
                        {{-- Title row --}}
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                            {{-- Name + type --}}
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-gray-900 fs-2 fw-bold me-2">
                                        {{ $contact->supplier_business_name ?? $contact->name }}
                                    </span>
                                    @if (!empty($contact->supplier_business_name) && $contact->supplier_business_name !== $contact->name)
                                        <span class="text-gray-500 fs-4">({{ $contact->name }})</span>
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                    <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                        <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        </i>
                                        @if ($is_supplier && $is_customer)
                                            <span class="badge badge-light-info">@lang('lang_v1.both')</span>
                                        @elseif ($is_supplier)
                                            <span class="badge badge-light-warning">@lang('lang_v1.supplier')</span>
                                        @else
                                            <span class="badge badge-light-primary">@lang('lang_v1.customer')</span>
                                        @endif
                                    </span>
                                    @if (!empty($contact->city) || !empty($contact->state))
                                        <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                            <i class="ki-duotone ki-geolocation fs-4 me-1">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            {{ implode(', ', array_filter([$contact->city, $contact->state])) }}
                                        </span>
                                    @endif
                                    @if (!empty($contact->email))
                                        <span class="d-flex align-items-center text-gray-500 mb-2">
                                            <i class="ki-duotone ki-sms fs-4 me-1">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            {{ $contact->email }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="d-flex my-4 gap-2">
                                @if ($can_edit_contact)
                                    <a href="#"
                                        class="btn btn-sm btn-light-primary js-open-contact-edit-tab">
                                        <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i>
                                        @lang('messages.edit')
                                    </a>
                                @endif
                                <a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, 'getPayContactDue'], [$contact->id]) }}?type={{ $is_supplier ? 'purchase' : 'sell' }}"
                                    class="btn btn-sm btn-primary pay_purchase_due">
                                    <i class="ki-duotone ki-dollar fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    @lang('lang_v1.pay')
                                </a>
                                {{-- Three-dot menu --}}
                                <div class="me-0">
                                    <button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                        <i class="ki-solid ki-dots-horizontal fs-2x"></i>
                                    </button>
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                                        data-kt-menu="true">
                                        <div class="menu-item px-3">
                                            <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">@lang('messages.actions')</div>
                                        </div>
                                        @if (auth()->user()->can('supplier.view') || auth()->user()->can('customer.view'))
                                            <div class="menu-item px-3">
                                                <a href="{{ action([\App\Http\Controllers\ContactController::class, 'getLedger']) }}?contact_id={{ $contact->id }}"
                                                    class="menu-link px-3">
                                                    <i class="ki-duotone ki-scroll fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                    @lang('lang_v1.ledger')
                                                </a>
                                            </div>
                                        @endif
                                        @if (auth()->user()->can('supplier.delete') || auth()->user()->can('customer.delete'))
                                            <div class="menu-item px-3">
                                                <a href="{{ action([\App\Http\Controllers\ContactController::class, 'destroy'], [$contact->id]) }}"
                                                    class="menu-link px-3 delete_contact_button text-danger">
                                                    <i class="ki-duotone ki-trash fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                                    @lang('messages.delete')
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Stats row --}}
                        <div class="d-flex flex-wrap flex-stack">
                            <div class="d-flex flex-column flex-grow-1 pe-8">
                                <div class="d-flex flex-wrap">
                                    {{-- Stat 1: Total Sales / Total Purchase --}}
                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <div class="fs-2 fw-bold">{{ $contact_stats['stat_1_value'] }}</div>
                                        </div>
                                        <div class="fw-semibold fs-6 text-gray-500">{{ $contact_stats['stat_1_label'] }}</div>
                                    </div>

                                    {{-- Stat 2: Outstanding Due --}}
                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-arrow-down fs-3 text-danger me-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <div class="fs-2 fw-bold">{{ $contact_stats['stat_2_value'] }}</div>
                                        </div>
                                        <div class="fw-semibold fs-6 text-gray-500">{{ $contact_stats['stat_2_label'] }}</div>
                                    </div>

                                    {{-- Stat 3: Advance Balance --}}
                                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <div class="fs-2 fw-bold">{{ $contact_stats['stat_3_value'] }}</div>
                                        </div>
                                        <div class="fw-semibold fs-6 text-gray-500">{{ $contact_stats['stat_3_label'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab strip (nav-line-tabs) --}}
                <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'overview' ? 'active' : '' }}"
                            href="#tab_overview" data-bs-toggle="tab" data-bs-target="#tab_overview">
                            @lang('messages.overview')
                        </a>
                    </li>
                    @if ($can_edit_contact)
                        <li class="nav-item mt-2 d-none">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'edit' ? 'active' : '' }}"
                                href="#tab_edit" data-bs-toggle="tab" data-bs-target="#tab_edit">
                                @lang('messages.edit')
                            </a>
                        </li>
                    @endif
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'ledger' ? 'active' : '' }}"
                            href="#tab_ledger" data-bs-toggle="tab" data-bs-target="#tab_ledger">
                            @lang('lang_v1.ledger')
                        </a>
                    </li>
                    @if ($is_supplier)
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'purchase' ? 'active' : '' }}"
                                href="#tab_purchases" data-bs-toggle="tab" data-bs-target="#tab_purchases" id="purchases-link">
                                @lang('purchase.purchases')
                            </a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'stock_report' ? 'active' : '' }}"
                                href="#tab_stock" data-bs-toggle="tab" data-bs-target="#tab_stock">
                                @lang('report.stock_report')
                            </a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'supplier_products' ? 'active' : '' }}"
                                href="#tab_supplier_products" data-bs-toggle="tab" data-bs-target="#tab_supplier_products">
                                @lang('lang_v1.supplier_products')
                            </a>
                        </li>
                    @endif
                    @if ($is_customer)
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'sales' ? 'active' : '' }}"
                                href="#tab_sales" data-bs-toggle="tab" data-bs-target="#tab_sales">
                                @lang('sale.sells')
                            </a>
                        </li>
                        @if (in_array('subscription', $enabled_modules))
                            <li class="nav-item mt-2">
                                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'subscriptions' ? 'active' : '' }}"
                                    href="#tab_subscriptions" data-bs-toggle="tab" data-bs-target="#tab_subscriptions">
                                    @lang('lang_v1.subscriptions')
                                </a>
                            </li>
                        @endif
                    @endif
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'payments' ? 'active' : '' }}"
                            href="#tab_payments" data-bs-toggle="tab" data-bs-target="#tab_payments">
                            @lang('sale.payments')
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'documents_and_notes' ? 'active' : '' }}"
                            href="#tab_documents" data-bs-toggle="tab" data-bs-target="#tab_documents">
                            @lang('lang_v1.documents_and_notes')
                        </a>
                    </li>
                    @if (in_array($contact->type, ['customer', 'both']) && session('business.enable_rp'))
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'reward_point' ? 'active' : '' }}"
                                href="#tab_reward" data-bs-toggle="tab" data-bs-target="#tab_reward">
                                {{ session('business.rp_name') ?? __('lang_v1.reward_points') }}
                            </a>
                        </li>
                    @endif
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'activities' ? 'active' : '' }}"
                            href="#tab_activities" data-bs-toggle="tab" data-bs-target="#tab_activities">
                            @lang('lang_v1.activities')
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $view_type === 'feeds' ? 'active' : '' }}"
                            href="#tab_feeds" data-bs-toggle="tab" data-bs-target="#tab_feeds">
                            Feeds
                        </a>
                    </li>
                    @if (!empty($contact_view_tabs))
                        @foreach ($contact_view_tabs as $key => $tabs)
                            @foreach ($tabs as $index => $value)
                                @if (!empty($value['tab_menu_path']))
                                    @php $tab_data = !empty($value['tab_data']) ? $value['tab_data'] : []; @endphp
                                    @include($value['tab_menu_path'], $tab_data)
                                @endif
                            @endforeach
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
        {{-- End Hero Navbar card --}}

        {{-- Tab content --}}
        <div class="tab-content">

            {{-- Overview: Profile Details card (overview.html pattern) --}}
            <div class="tab-pane fade {{ $view_type === 'overview' ? 'show active' : '' }}" id="tab_overview">
                <div class="card mb-5 mb-xl-10" id="kt_profile_details_view">
                    <div class="card-header cursor-pointer">
                        <div class="card-title m-0">
                            <h3 class="fw-bold m-0">@lang('lang_v1.contact_info')</h3>
                        </div>
                        @if ($can_edit_contact)
                            <a href="#"
                                class="btn btn-sm btn-primary align-self-center js-open-contact-edit-tab">
                                @lang('messages.edit')
                            </a>
                        @endif
                    </div>
                    <div class="card-body p-9">
                        <div class="row mb-7">
                            <label class="col-lg-4 fw-semibold text-muted">@lang('contact.name')</label>
                            <div class="col-lg-8">
                                <span class="fw-bold fs-6 text-gray-800">{{ $contact->name }}</span>
                            </div>
                        </div>
                        @if (!empty($contact->supplier_business_name))
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('business.business_name')</label>
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-semibold text-gray-800 fs-6">{{ $contact->supplier_business_name }}</span>
                                </div>
                            </div>
                        @endif
                        @if (!empty($contact->email))
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('business.email')</label>
                                <div class="col-lg-8">
                                    <a href="mailto:{{ $contact->email }}" class="fw-semibold fs-6 text-gray-800 text-hover-primary">
                                        {{ $contact->email }}
                                    </a>
                                </div>
                            </div>
                        @endif
                        @if (!empty($contact->mobile))
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">
                                    @lang('contact.mobile')
                                </label>
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span class="fw-bold fs-6 text-gray-800 me-2">{{ $contact->mobile }}</span>
                                    <span class="badge badge-light-success">@lang('messages.verified')</span>
                                </div>
                            </div>
                        @endif
                        @if (!empty($contact->tax_number))
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('contact.tax_no')</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $contact->tax_number }}</span>
                                </div>
                            </div>
                        @endif
                        @if (!empty($contact->pay_term_number))
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('contact.pay_term')</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">
                                        {{ $contact->pay_term_number }} {{ $contact->pay_term_type }}
                                    </span>
                                </div>
                            </div>
                        @endif
                        @if ($is_customer && !empty($contact->credit_limit))
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.credit_limit')</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">@format_currency($contact->credit_limit)</span>
                                </div>
                            </div>
                        @endif
                        @php
                            $address_parts = array_filter([
                                $contact->address_line_1 ?? null,
                                $contact->address_line_2 ?? null,
                                $contact->city ?? null,
                                $contact->state ?? null,
                                $contact->country ?? null,
                                $contact->zip_code ?? null,
                            ]);
                        @endphp
                        @if (!empty($address_parts))
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('business.address')</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ implode(', ', $address_parts) }}</span>
                                </div>
                            </div>
                        @endif
                        <div class="row mb-7">
                            <label class="col-lg-4 fw-semibold text-muted">@lang('sale.status')</label>
                            <div class="col-lg-8">
                                @if ($contact->contact_status === 'active')
                                    <span class="badge badge-light-success">@lang('business.is_active')</span>
                                @else
                                    <span class="badge badge-light-danger">@lang('lang_v1.inactive')</span>
                                @endif
                            </div>
                        </div>
                        @if ($contact->opening_balance > 0)
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('account.opening_balance')</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">@format_currency($contact->opening_balance)</span>
                                </div>
                            </div>
                        @endif
                        <div class="row mb-7">
                            <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.added_on')</label>
                            <div class="col-lg-8">
                                <span class="fw-bold fs-6 text-gray-800">{{ $contact->created_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- End Overview tab --}}

            @if ($can_edit_contact)
                <div class="tab-pane fade {{ $view_type === 'edit' ? 'show active' : '' }}" id="tab_edit">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-header cursor-pointer">
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">@lang('contact.edit_contact')</h3>
                            </div>
                        </div>
                        <div class="card-body p-9">
                            <div id="contact_edit_tab_container" data-edit-url="{{ route('contacts.edit', ['contact' => $contact->id]) }}">
                                <div class="text-muted">Loading edit form...</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Ledger tab --}}
            <div class="tab-pane fade {{ $view_type === 'ledger' ? 'show active' : '' }}" id="tab_ledger">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-body p-0">
                        @include('contact.partials.ledger_tab')
                    </div>
                </div>
            </div>

            {{-- Purchases tab (supplier only) --}}
            @if ($is_supplier)
                <div class="tab-pane fade {{ $view_type === 'purchase' ? 'show active' : '' }}" id="tab_purchases">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body">
                            <div class="row mb-5">
                                <div class="col-md-4">
                                    {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                                    {!! Form::text('purchase_list_filter_date_range', null, [
                                        'placeholder' => __('lang_v1.select_a_date_range'),
                                        'class' => 'form-control form-control-solid',
                                        'readonly',
                                    ]) !!}
                                </div>
                            </div>
                            @include('purchase.partials.purchase_table')
                        </div>
                    </div>
                </div>

                {{-- Stock Report tab (supplier only) --}}
                <div class="tab-pane fade {{ $view_type === 'stock_report' ? 'show active' : '' }}" id="tab_stock">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body">
                            @include('contact.partials.stock_report_tab')
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $view_type === 'supplier_products' ? 'show active' : '' }}" id="tab_supplier_products">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body">
                            @include('contact.partials.supplier_products_tab')
                        </div>
                    </div>
                </div>
            @endif

            {{-- Sales tab (customer only) --}}
            @if ($is_customer)
                <div class="tab-pane fade {{ $view_type === 'sales' ? 'show active' : '' }}" id="tab_sales">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body">
                            @component('components.widget')
                                @include('sell.partials.sell_list_filters', ['only' => ['sell_list_filter_payment_status', 'sell_list_filter_date_range', 'only_subscriptions']])
                            @endcomponent
                            @include('sale_pos.partials.sales_table')
                        </div>
                    </div>
                </div>

                @if (in_array('subscription', $enabled_modules))
                    <div class="tab-pane fade {{ $view_type === 'subscriptions' ? 'show active' : '' }}" id="tab_subscriptions">
                        <div class="card mb-5 mb-xl-10">
                            <div class="card-body">
                                @include('contact.partials.subscriptions')
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Payments tab --}}
            <div class="tab-pane fade {{ $view_type === 'payments' ? 'show active' : '' }}" id="tab_payments">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-body">
                        <div id="contact_payments_div" style="height: 500px; overflow-y: scroll;"></div>
                    </div>
                </div>
            </div>

            {{-- Documents & Notes tab --}}
            <div class="tab-pane fade {{ $view_type === 'documents_and_notes' ? 'show active' : '' }}" id="tab_documents">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-body">
                        @include('contact.partials.documents_and_notes_tab')
                    </div>
                </div>
            </div>

            {{-- Reward Points tab --}}
            @if (in_array($contact->type, ['customer', 'both']) && session('business.enable_rp'))
                <div class="tab-pane fade {{ $view_type === 'reward_point' ? 'show active' : '' }}" id="tab_reward">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body">
                            @if ($reward_enabled)
                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-5 d-inline-flex flex-column">
                                    <div class="fs-2 fw-bold">{{ $contact->total_rp ?? 0 }}</div>
                                    <div class="fw-semibold fs-6 text-gray-500">{{ session('business.rp_name') }}</div>
                                </div>
                            @endif
                            <table class="table align-middle table-row-dashed fs-6 gy-5"
                                id="rp_log_table" width="100%">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('lang_v1.earned')</th>
                                        <th>@lang('lang_v1.redeemed')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Activity tab --}}
            <div class="tab-pane fade {{ $view_type === 'activities' ? 'show active' : '' }}" id="tab_activities">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-body">
                        @include('activity_log.activities')
                    </div>
                </div>
            </div>

            {{-- Feeds tab --}}
            <div class="tab-pane fade {{ $view_type === 'feeds' ? 'show active' : '' }}" id="tab_feeds">
                @include('contact.partials.feeds_tab')
            </div>

            {{-- Module tabs --}}
            @if (!empty($contact_view_tabs))
                @foreach ($contact_view_tabs as $key => $tabs)
                    @foreach ($tabs as $index => $value)
                        @if (!empty($value['tab_content_path']))
                            @php $tab_data = !empty($value['tab_data']) ? $value['tab_data'] : []; @endphp
                            @include($value['tab_content_path'], $tab_data)
                        @endif
                    @endforeach
                @endforeach
            @endif

        </div>
        {{-- End tab content --}}

        {{-- Print section (hidden) --}}
        <div class="d-none print_table_part">
            <div style="width: 100%; display: flex;">
                <div style="width: 25%; padding: 0 10px;">@include('contact.contact_basic_info')</div>
                <div style="width: 25%; padding: 0 10px;">@include('contact.contact_more_info')</div>
                @if ($contact->type !== 'customer')
                    <div style="width: 25%; padding: 0 10px;">@include('contact.contact_tax_info')</div>
                @endif
                <div style="width: 25%; padding: 0 10px;">@include('contact.contact_payment_info')</div>
            </div>
        </div>

        {{-- Modals --}}
        <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade" id="edit_ledger_discount_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        @include('ledger_discount.create')

    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
    var contactActiveTab = @json($view_type ?? 'ledger');
    var canEditContact = @json($can_edit_contact);
    var contactFeedsLoaded = false;
    var contactFeedsProvider = 'google';
    var contactFeedsLimit = 30;
    var contactEditTabLoaded = false;
    var contactFeedsListUrl = "{{ action([\App\Http\Controllers\ContactController::class, 'getContactFeeds'], [$contact->id]) }}";
    var contactFeedsLoadUrl = "{{ action([\App\Http\Controllers\ContactController::class, 'loadContactFeeds'], [$contact->id]) }}";
    var contactFeedsUpdateUrl = "{{ action([\App\Http\Controllers\ContactController::class, 'updateContactFeeds'], [$contact->id]) }}";
    var contactEditUrl = "{{ route('contacts.edit', ['contact' => $contact->id]) }}";
    var supplierProductsConfig = @json($supplier_products_config ?? []);
    var supplierProductsTable = null;
    var supplierProductsTabLoaded = false;

    $(document).ready(function () {
        var initContactEditTabForm = function ($container) {
            var $form = $container.find('form#contact_edit_form');
            if (! $form.length) {
                return;
            }
            $form.attr('novalidate', 'novalidate');

            var $contactType = $form.find('select#contact_type');
            var $assignDiv = $form.find('div.contact_assign_div');
            var $leadAdditionalDiv = $form.find('div.lead_additional_div');
            var $firstName = $form.find('input[name="first_name"]');
            var $leadUsers = $form.find('#user_id');

            var syncDynamicRequiredFields = function () {
                var selectedRadio = $form.find('input[name="contact_type_radio"]:checked').val();
                var isIndividual = selectedRadio === 'individual';
                var isLead = $contactType.val() === 'lead';

                if ($firstName.length) {
                    $firstName.prop('required', isIndividual);
                }
                if ($leadUsers.length) {
                    $leadUsers.prop('required', isLead);
                }
            };

            $form.find('input[type=radio][name="contact_type_radio"]')
                .off('change.contact_edit_tab_radio')
                .on('change.contact_edit_tab_radio', function () {
                    if (this.value === 'individual') {
                        $form.find('div.individual').show();
                        $form.find('div.business').hide();
                    } else if (this.value === 'business') {
                        $form.find('div.individual').hide();
                        $form.find('div.business').show();
                    }
                    syncDynamicRequiredFields();
                });

            var toggleExportDiv = function () {
                if ($form.find('#is_customer_export').is(':checked')) {
                    $form.find('div.export_div').show();
                } else {
                    $form.find('div.export_div').hide();
                }
            };
            toggleExportDiv();

            $form.find('#is_customer_export')
                .off('change.contact_edit_tab_export')
                .on('change.contact_edit_tab_export', toggleExportDiv);

            $form.find('.more_btn')
                .off('click.contact_edit_tab_more')
                .on('click.contact_edit_tab_more', function () {
                    $form.find($(this).data('target')).toggleClass('hide');
                });

            $leadAdditionalDiv.hide();
            $assignDiv.removeClass('hide').show();

            var syncContactType = function () {
                var t = $contactType.val();
                if (t === 'supplier') {
                    $form.find('div.supplier_fields').fadeIn();
                    $form.find('div.customer_fields').fadeOut();
                    $assignDiv.removeClass('hide').fadeIn();
                } else if (t === 'both') {
                    $form.find('div.supplier_fields').fadeIn();
                    $form.find('div.customer_fields').fadeIn();
                    $assignDiv.removeClass('hide').fadeIn();
                } else if (t === 'customer') {
                    $form.find('div.customer_fields').fadeIn();
                    $form.find('div.supplier_fields').fadeOut();
                    $assignDiv.removeClass('hide').fadeIn();
                } else if (t === 'lead') {
                    $form.find('div.customer_fields').fadeOut();
                    $form.find('div.supplier_fields').fadeOut();
                    $form.find('div.opening_balance').fadeOut();
                    $form.find('div.pay_term').fadeOut();
                    $leadAdditionalDiv.fadeIn();
                    $assignDiv.addClass('hide').fadeOut();
                    $form.find('div.shipping_addr_div').hide();
                }
                syncDynamicRequiredFields();
            };
            syncContactType();

            $contactType
                .off('change.contact_edit_tab_type')
                .on('change.contact_edit_tab_type', syncContactType);

            $form.find('.select2').each(function () {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    dropdownParent: $container,
                    width: '100%',
                });
            });

            $form.off('submit.contact_edit_tab').on('submit.contact_edit_tab', function (e) {
                e.preventDefault();
                var $submitButton = $form.find('button[type="submit"]').first();
                __disable_submit_button($submitButton);

                $.ajax({
                    method: 'POST',
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: $form.serialize(),
                    success: function (result) {
                        if (result.success === true || result.success == true) {
                            toastr.success(result.msg);
                            window.location = "{{ route('contacts.show', ['contact' => $contact->id]) }}?view=overview";
                        } else {
                            toastr.error(result.msg || "{{ __('messages.something_went_wrong') }}");
                            $submitButton.prop('disabled', false);
                        }
                    },
                    error: function () {
                        toastr.error("{{ __('messages.something_went_wrong') }}");
                        $submitButton.prop('disabled', false);
                    },
                });
            });
        };

        var loadContactEditTab = function () {
            if (!canEditContact || contactEditTabLoaded) {
                return;
            }

            var $container = $('#contact_edit_tab_container');
            if (! $container.length) {
                return;
            }

            $container.html('<div class="text-muted">Loading edit form...</div>');
            $.ajax({
                url: contactEditUrl,
                dataType: 'html',
                success: function (result) {
                    var $response = $('<div>').html(result);
                    var $form = $response.find('form#contact_edit_form').first();

                    if (! $form.length) {
                        $container.html('<div class="text-danger">Unable to load edit form.</div>');
                        return;
                    }

                    $form.find('button[data-bs-dismiss="modal"], button[data-dismiss="modal"]').remove();
                    $container.html($form);
                    initContactEditTabForm($container);
                    contactEditTabLoaded = true;
                },
                error: function () {
                    $container.html('<div class="text-danger">Unable to load edit form.</div>');
                },
            });
        };

        var buildSupplierProductDeleteUrl = function (productId) {
            if (!supplierProductsConfig.destroy_url_template) {
                return '';
            }

            return supplierProductsConfig.destroy_url_template.replace('__PRODUCT_ID__', productId);
        };

        var initializeSupplierProductsTab = function () {
            if (!supplierProductsConfig.list_url || !$('#supplier_products_table').length) {
                return;
            }

            if (!supplierProductsTabLoaded) {
                supplierProductsTable = $('#supplier_products_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader: false,
                    ajax: {
                        url: supplierProductsConfig.list_url,
                    },
                    columns: [
                        { data: 'product_name', name: 'p.name' },
                        { data: 'sku', name: 'p.sku' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });
                supplierProductsTabLoaded = true;
            } else if (supplierProductsTable) {
                supplierProductsTable.ajax.reload();
            }

            if (supplierProductsConfig.can_manage && $('#supplier_products_select').length && !$('#supplier_products_select').data('supplier-products-select2')) {
                $('#supplier_products_select').select2({
                    width: '100%',
                    ajax: {
                        url: supplierProductsConfig.products_search_url,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                term: params.term || '',
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data,
                            };
                        },
                    },
                    minimumInputLength: 1,
                });
                $('#supplier_products_select').data('supplier-products-select2', true);
            }
        };

        // Activate correct tab from $view_type
        var tabMap = {
            'ledger': '#tab_ledger',
            'edit': '#tab_edit',
            'purchase': '#tab_purchases',
            'stock_report': '#tab_stock',
            'supplier_products': '#tab_supplier_products',
            'sales': '#tab_sales',
            'subscriptions': '#tab_subscriptions',
            'payments': '#tab_payments',
            'documents_and_notes': '#tab_documents',
            'reward_point': '#tab_reward',
            'activities': '#tab_activities',
            'feeds': '#tab_feeds',
            'overview': '#tab_overview',
        };

        if (tabMap[contactActiveTab]) {
            var targetEl = document.querySelector('[data-bs-target="' + tabMap[contactActiveTab] + '"]');
            if (targetEl) {
                var tabInstance = new bootstrap.Tab(targetEl);
                tabInstance.show();
            }
        }

        $(document).on('click', '.js-open-contact-edit-tab', function (e) {
            e.preventDefault();
            var editTabTrigger = document.querySelector('[data-bs-target="#tab_edit"]');
            if (editTabTrigger) {
                var editTabInstance = new bootstrap.Tab(editTabTrigger);
                editTabInstance.show();
            }
        });

        $('#ledger_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#ledger_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            }
        );
        $('#ledger_date_range, #ledger_location').change(function () {
            get_contact_ledger();
        });
        get_contact_ledger();

        rp_log_table = $('#rp_log_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            aaSorting: [[0, 'desc']],
            ajax: '/sells?customer_id={{ $contact->id }}&rewards_only=true',
            columns: [
                { data: 'transaction_date', name: 'transactions.transaction_date' },
                { data: 'invoice_no', name: 'transactions.invoice_no' },
                { data: 'rp_earned', name: 'transactions.rp_earned' },
                { data: 'rp_redeemed', name: 'transactions.rp_redeemed' },
            ]
        });

        supplier_stock_report_table = $('#supplier_stock_report_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            'ajax': {
                url: "{{ action([\App\Http\Controllers\ContactController::class, 'getSupplierStockReport'], [$contact->id]) }}",
                data: function (d) {
                    d.location_id = $('#sr_location_id').val();
                }
            },
            columns: [
                { data: 'product_name', name: 'p.name' },
                { data: 'sub_sku', name: 'v.sub_sku' },
                { data: 'purchase_quantity', name: 'purchase_quantity', searchable: false },
                { data: 'total_quantity_sold', name: 'total_quantity_sold', searchable: false },
                { data: 'total_quantity_transfered', name: 'total_quantity_transfered', searchable: false },
                { data: 'total_quantity_returned', name: 'total_quantity_returned', searchable: false },
                { data: 'current_stock', name: 'current_stock', searchable: false },
                { data: 'stock_price', name: 'stock_price', searchable: false }
            ],
            fnDrawCallback: function () {
                __currency_convert_recursively($('#supplier_stock_report_table'));
            },
        });

        $('#sr_location_id').change(function () {
            supplier_stock_report_table.ajax.reload();
        });

        $('#contact_id').change(function () {
            if ($(this).val()) {
                window.location = "{{ url('/contacts') }}/" + $(this).val();
            }
        });

        // Reload sell table when sales tab is shown
        $('[data-bs-target="#tab_sales"]').on('shown.bs.tab', function () {
            if (typeof sell_table !== 'undefined') {
                sell_table.ajax.reload();
            }
        });

        // Load payments on first visit to payments tab
        $('[data-bs-target="#tab_payments"]').one('shown.bs.tab', function () {
            get_contact_payments();
        });

        // Reload purchase table when purchases tab is shown
        $('[data-bs-target="#tab_purchases"]').on('shown.bs.tab', function () {
            if (typeof purchase_table !== 'undefined') {
                purchase_table.ajax.reload();
            }
        });

        $('[data-bs-target="#tab_supplier_products"]').on('shown.bs.tab', function () {
            initializeSupplierProductsTab();
        });

        $(document).on('click', '#add_supplier_products_btn', function () {
            if (!supplierProductsConfig.can_manage) {
                return;
            }

            var productIds = ($('#supplier_products_select').val() || []).map(function (value) {
                return parseInt(value, 10);
            }).filter(function (value) {
                return value > 0;
            });

            if (!productIds.length) {
                toastr.error("{{ __('lang_v1.supplier_products_select_required') }}");
                return;
            }

            if (productIds.length > (supplierProductsConfig.max_product_ids || 500)) {
                toastr.error("{{ __('lang_v1.supplier_products_max_limit_exceeded') }}");
                return;
            }

            var $button = $(this);
            __disable_submit_button($button);

            $.ajax({
                method: 'POST',
                url: supplierProductsConfig.store_url,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': supplierProductsConfig.csrf_token,
                },
                data: {
                    product_ids: productIds,
                },
                success: function (result) {
                    if (result.success === true || result.success == true) {
                        toastr.success(result.msg);
                        $('#supplier_products_select').val(null).trigger('change');
                        if (supplierProductsTable) {
                            supplierProductsTable.ajax.reload();
                        }
                    } else {
                        toastr.error(result.msg || "{{ __('messages.something_went_wrong') }}");
                    }
                },
                error: function () {
                    toastr.error("{{ __('messages.something_went_wrong') }}");
                },
                complete: function () {
                    $button.prop('disabled', false);
                },
            });
        });

        $(document).on('click', '.js-remove-supplier-product', function () {
            if (!supplierProductsConfig.can_manage) {
                return;
            }

            var href = $(this).data('href');
            var productId = $(this).data('product-id');
            if (!href && productId) {
                href = buildSupplierProductDeleteUrl(productId);
            }
            if (!href) {
                toastr.error("{{ __('messages.something_went_wrong') }}");
                return;
            }

            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function (willDelete) {
                if (!willDelete) {
                    return;
                }

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': supplierProductsConfig.csrf_token,
                    },
                    success: function (result) {
                        if (result.success === true || result.success == true) {
                            toastr.success(result.msg);
                            if (supplierProductsTable) {
                                supplierProductsTable.ajax.reload();
                            }
                        } else {
                            toastr.error(result.msg || "{{ __('messages.something_went_wrong') }}");
                        }
                    },
                    error: function () {
                        toastr.error("{{ __('messages.something_went_wrong') }}");
                    },
                });
            });
        });

        // Load feeds when tab is opened the first time
        $('[data-bs-target="#tab_feeds"]').one('shown.bs.tab', function () {
            initialize_contact_feeds();
        });

        $('[data-bs-target="#tab_edit"]').one('shown.bs.tab', function () {
            loadContactEditTab();
        });

        if (contactActiveTab === 'feeds') {
            initialize_contact_feeds();
        }
        if (contactActiveTab === 'edit') {
            loadContactEditTab();
        }
        if (contactActiveTab === 'supplier_products') {
            initializeSupplierProductsTab();
        }

        $('#discount_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });

        $(document).on('submit', 'form#add_discount_form, form#edit_discount_form', function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function (result) {
                    if (result.success === true) {
                        $('div#add_discount_modal').modal('hide');
                        $('div#edit_ledger_discount_modal').modal('hide');
                        toastr.success(result.msg);
                        form[0].reset();
                        form.find('button[type="submit"]').removeAttr('disabled');
                        get_contact_ledger();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', 'button.delete_ledger_discount', function () {
            swal({
                title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true,
            }).then(function (willDelete) {
                if (willDelete) {
                    var href = $(this).data('href');
                    $.ajax({
                        method: 'DELETE', url: href, dataType: 'json',
                        success: function (result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                get_contact_ledger();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            }.bind(this));
        });
    });

    $(document).on('shown.bs.modal', '#edit_ledger_discount_modal', function () {
        $('#edit_ledger_discount_modal').find('#edit_discount_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });
    });

    $("input.transaction_types, input#show_payments").on('ifChanged', function () {
        get_contact_ledger();
    });

    $(document).on('change', 'input[name="ledger_format"]', function () {
        get_contact_ledger();
    });

    $(document).on('click', '#contact_payments_pagination a', function (e) {
        e.preventDefault();
        get_contact_payments($(this).attr('href'));
    });

    function get_contact_payments(url) {
        url = url || "{{ action([\App\Http\Controllers\ContactController::class, 'getContactPayments'], [$contact->id]) }}";
        $.ajax({
            url: url, dataType: 'html',
            success: function (result) {
                $('#contact_payments_div').fadeOut(400, function () {
                    $('#contact_payments_div').html(result).fadeIn(400);
                });
            },
        });
    }

    $(document).on('click', '#update_contact_feeds_btn', function (e) {
        e.preventDefault();
        sync_contact_feeds('update', $(this));
    });

    function initialize_contact_feeds() {
        if (contactFeedsLoaded) {
            get_contact_feeds();
            return;
        }

        sync_contact_feeds('load', $('#update_contact_feeds_btn'));
    }

    function get_contact_feeds() {
        var $container = $('#contact_feeds_div');

        $container.html(
            '<div class="d-flex align-items-center justify-content-center py-10">' +
            '<span class="spinner-border spinner-border-sm me-2"></span>' +
            '<span class="text-muted fw-semibold">Loading feeds...</span>' +
            '</div>'
        );

        $.ajax({
            method: 'GET',
            url: contactFeedsListUrl,
            dataType: 'html',
            data: { provider: contactFeedsProvider, limit: contactFeedsLimit },
            success: function (result) {
                $container.html(result);
            },
            error: function () {
                $container.html(
                    '<div class="alert alert-light-danger mb-0">' +
                    'Unable to load feeds. Please try again.' +
                    '</div>'
                );
            },
        });
    }

    function sync_contact_feeds(action, $button) {
        var endpoint = action === 'update' ? contactFeedsUpdateUrl : contactFeedsLoadUrl;
        var payload = { provider: contactFeedsProvider, limit: contactFeedsLimit };

        $button.attr('data-kt-indicator', 'on').prop('disabled', true);
        if (action === 'load') {
            $('#contact_feeds_div').html(
                '<div class=\"d-flex align-items-center justify-content-center py-10\">' +
                '<span class=\"spinner-border spinner-border-sm me-2\"></span>' +
                '<span class=\"text-muted fw-semibold\">Searching Google for related news...</span>' +
                '</div>'
            );
        }

        $.ajax({
            method: 'POST',
            url: endpoint,
            dataType: 'json',
            data: payload,
            success: function (result) {
                render_contact_feeds_summary(result);

                if (result.success) {
                    toastr.success(result.msg);
                } else {
                    toastr.warning(result.msg);
                }

                contactFeedsLoaded = true;
                get_contact_feeds();
            },
            error: function (xhr) {
                var result = xhr.responseJSON || {
                    success: false,
                    msg: 'Unable to sync feeds at the moment.',
                    inserted_count: 0,
                    skipped_count: 0,
                    existing_count: 0,
                    provider: contactFeedsProvider,
                    last_synced_at: null,
                };
                render_contact_feeds_summary(result);
                toastr.error(result.msg || 'Unable to sync feeds at the moment.');
            },
            complete: function () {
                $button.removeAttr('data-kt-indicator').prop('disabled', false);
            },
        });
    }

    function render_contact_feeds_summary(result) {
        var $summary = $('#contact_feeds_summary');
        var stateClass = result.success ? 'alert-light-success' : 'alert-light-warning';

        $summary
            .removeClass('d-none alert-light-success alert-light-warning alert-light-primary')
            .addClass(stateClass)
            .html(
                '<div class="fw-semibold">' + (result.msg || '') + '</div>' +
                '<div class="fs-7 text-gray-700 mt-1">' +
                    'Inserted: ' + (result.inserted_count || 0) + ' | ' +
                    'Skipped: ' + (result.skipped_count || 0) + ' | ' +
                    'Existing: ' + (result.existing_count || 0) +
                    (result.last_synced_at ? ' | Last sync: ' + result.last_synced_at : '') +
                '</div>'
            );
    }

    function get_contact_ledger() {
        var start_date = '', end_date = '';
        var transaction_types = $('input.transaction_types:checked').map(function (i, e) { return e.value; }).toArray();
        var show_payments = $('input#show_payments').is(':checked');
        var location_id = $('#ledger_location').val();

        if ($('#ledger_date_range').val()) {
            start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        var format = $('input[name="ledger_format"]:checked').val();
        $.ajax({
            url: '/contacts/ledger?contact_id={{ $contact->id }}',
            data: { start_date, end_date, transaction_types, show_payments, format, location_id },
            dataType: 'html',
            success: function (result) {
                $('#contact_ledger_div').html(result);
                __currency_convert_recursively($('#contact_ledger_div'));
                $('#ledger_table').DataTable({
                    searching: false, ordering: false, paging: false, fixedHeader: false, dom: 't'
                });
            },
        });
    }

    $(document).on('click', '#send_ledger', function () {
        var start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        var end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        var format = $('input[name="ledger_format"]:checked').val();
        var location_id = $('#ledger_location').val();
        var url = "{{ action([\App\Http\Controllers\NotificationController::class, 'getTemplate'], [$contact->id, 'send_ledger']) }}" +
            '?start_date=' + start_date + '&end_date=' + end_date + '&format=' + format + '&location_id=' + location_id;
        $.ajax({
            url: url, dataType: 'html',
            success: function (result) {
                $('.view_modal').html(result).modal('show');
            },
        });
    });

    $(document).on('click', '#print_ledger_pdf', function () {
        var start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        var end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        var format = $('input[name="ledger_format"]:checked').val();
        var location_id = $('#ledger_location').val();
        var url = $(this).data('href') + '&start_date=' + start_date + '&end_date=' + end_date + '&format=' + format + '&location_id=' + location_id;
        window.open(url);
    });
</script>

@include('sale_pos.partials.sale_table_javascript')
<script src="{{ asset('assets/app/js/payment.js?v=' . $asset_v) }}"></script>
@if ($is_supplier)
    <script>
        var customFieldVisibility = @json($purchase_custom_field_visibility);
    </script>
    <script src="{{ asset('assets/app/js/purchase.js?v=' . $asset_v) }}"></script>
@endif

@include('documents_and_notes.document_and_note_js')

@if (!empty($contact_view_tabs))
    @foreach ($contact_view_tabs as $key => $tabs)
        @foreach ($tabs as $index => $value)
            @if (!empty($value['module_js_path']))
                @include($value['module_js_path'])
            @endif
        @endforeach
    @endforeach
@endif

<script type="text/javascript">
    $(document).ready(function () {
        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                if (typeof purchase_table !== 'undefined') { purchase_table.ajax.reload(); }
            }
        );
        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function () {
            $('#purchase_list_filter_date_range').val('');
            if (typeof purchase_table !== 'undefined') { purchase_table.ajax.reload(); }
        });
    });
</script>

@include('sale_pos.partials.subscriptions_table_javascript', ['contact_id' => $contact->id])
@endsection
