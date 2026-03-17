@extends('layouts.app')
@section('title', __('business.business_settings'))

@section('css')
<style>
    #business-settings-panes .form-group {
        margin-bottom: 1.5rem;
    }

    #business-settings-panes .form-label,
    #business-settings-panes label:not(.form-check-label) {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--bs-gray-700);
        margin-bottom: 0.5rem;
    }

    #business-settings-panes .form-control.form-control-solid,
    #business-settings-panes .form-select.form-select-solid {
        background-color: var(--bs-gray-100);
        border-color: var(--bs-gray-300);
        color: var(--bs-gray-700);
    }

    #business-settings-panes .form-control.form-control-solid:focus,
    #business-settings-panes .form-select.form-select-solid:focus {
        border-color: var(--bs-primary);
        background-color: var(--bs-white);
        box-shadow: 0 0 0 .2rem rgba(54, 153, 255, .15);
    }

    #business-settings-panes .select2-container--default .select2-selection--single,
    #business-settings-panes .select2-container--default .select2-selection--multiple {
        background-color: var(--bs-gray-100);
        border: 1px solid var(--bs-gray-300);
        border-radius: 0.475rem;
        min-height: calc(1.5em + 1.5rem + 2px);
    }

    #business-settings-panes .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--bs-gray-700);
        line-height: calc(1.5em + 1.5rem);
        padding-left: 0.95rem;
    }

    #business-settings-panes .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 50%;
        transform: translateY(-50%);
        right: 0.65rem;
    }

    #business-settings-panes .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        padding: 0.4rem 0.75rem;
    }

    #business-settings-panes .input-group-addon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.75rem;
        padding: 0.5rem 0.75rem;
        background: var(--bs-gray-100);
        border: 1px solid var(--bs-gray-300);
        border-radius: 0.475rem 0 0 0.475rem;
        color: var(--bs-gray-600);
        font-weight: 500;
        white-space: nowrap;
    }

    #business-settings-panes .input-group {
        display: flex;
        align-items: stretch;
        flex-wrap: nowrap;
    }

    #business-settings-panes .input-group > .form-control,
    #business-settings-panes .input-group > .form-select,
    #business-settings-panes .input-group > .select2-container,
    #business-settings-panes .input-group > select.select2-hidden-accessible + .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
        min-width: 0;
        margin-bottom: 0;
    }

    #business-settings-panes .input-group .input-group-addon + .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    #business-settings-panes .input-group .input-group-addon + .form-select {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    #business-settings-panes .input-group .input-group-addon + select + .select2-container .select2-selection--single,
    #business-settings-panes .input-group .input-group-addon + select + .select2-container .select2-selection--multiple {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: 0;
    }

    #business-settings-panes .help-block {
        margin-top: 0.35rem;
        margin-bottom: 0;
        font-size: 0.85rem;
        color: var(--bs-gray-600);
    }

    #business-settings-panes .checkbox,
    #business-settings-panes .radio {
        margin-top: 0.35rem;
        margin-bottom: 0.35rem;
    }

    #business-settings-panes .form-check.form-check-custom {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        min-height: 1.5rem;
    }

    #business-settings-panes .form-check-input {
        border: 1px solid var(--bs-gray-400);
        background-color: var(--bs-white);
    }

    #business-settings-panes .form-check-label {
        margin-bottom: 0;
        font-weight: 500;
        color: var(--bs-gray-700);
    }

    #business-settings-panes .start-date-picker {
        cursor: pointer;
    }
</style>
@endsection

@section('toolbar')
<div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">@lang('business.business_settings')</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ action([\App\Http\Controllers\HomeController::class, 'index']) }}" class="text-gray-600 text-hover-primary">@lang('lang_v1.home')</a>
                </li>
                <li class="breadcrumb-item text-gray-600">@lang('business.business')</li>
                <li class="breadcrumb-item text-gray-500">@lang('business.business_settings')</li>
            </ul>
        </div>

        <div class="d-flex align-items-center py-2">
            <div class="me-4">
                @include('layouts.partials.search_settings')
            </div>
            <button type="submit" class="btn btn-sm btn-primary" id="business-settings-submit" form="bussiness_edit_form">
                @lang('business.update_settings')
            </button>
        </div>
    </div>
</div>
@endsection

@section('content')
{!! Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postBusinessSettings']), 'method' => 'post', 'id' => 'bussiness_edit_form', 'files' => true]) !!}
<div id="business-settings-tabs" class="row g-5 g-xxl-8">
    <div class="col-12 col-xxl-4">
        <div class="card mb-5 mb-xl-10">
            <div class="card-body p-5">
                <ul class="nav nav-tabs nav-pills flex-row border-0 flex-md-column me-5 mb-3 mb-md-0 fs-6 min-w-lg-200px business-settings-nav">
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link active btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-shop fs-3 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('business.business')</span>
                                <span class="fs-8 text-muted">@lang('business.business_settings')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-bill fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('business.tax') @show_tooltip(__('tooltip.business_tax'))</span>
                                <span class="fs-8 text-muted">@lang('business.tax')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-duotone ki-basket fs-3 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('business.product')</span>
                                <span class="fs-8 text-muted">@lang('product.products')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-warning">
                                    <i class="ki-duotone ki-address-book fs-3 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('contact.contact')</span>
                                <span class="fs-8 text-muted">@lang('contact.contacts')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-danger">
                                    <i class="ki-duotone ki-chart-line fs-3 text-danger"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('business.sale')</span>
                                <span class="fs-8 text-muted">@lang('business.sale')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-credit-cart fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('sale.pos_sale')</span>
                                <span class="fs-8 text-muted">@lang('sale.pos_sale')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-monitor fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.display_screen')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.display_screen')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-duotone ki-handcart fs-3 text-info"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('purchase.purchases')</span>
                                <span class="fs-8 text-muted">@lang('purchase.purchases')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-warning">
                                    <i class="ki-duotone ki-dollar fs-3 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.payment')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.payment')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-danger">
                                    <i class="ki-duotone ki-chart-pie fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('business.dashboard')</span>
                                <span class="fs-8 text-muted">@lang('business.dashboard')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-setting-2 fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('business.system')</span>
                                <span class="fs-8 text-muted">@lang('business.system')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-tag fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.prefixes')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.prefixes')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-duotone ki-sms fs-3 text-info"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.email_settings')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.email_settings')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-warning">
                                    <i class="ki-duotone ki-message-text-2 fs-3 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.sms_settings')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.sms_settings')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-danger">
                                    <i class="ki-duotone ki-award fs-3 text-danger"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.reward_point_settings')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.reward_point_settings')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-grid fs-3 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.modules')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.modules')</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a href="#" class="nav-link btn btn-flex text-start m-0 p-4 border border-gray-200 border-hover-primary rounded btn-color-gray-700 btn-active-light-primary btn-active-color-primary w-100">
                            <span class="symbol symbol-35px me-3">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-text-align-left fs-3 text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </span>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fw-bold fs-6">@lang('lang_v1.custom_labels')</span>
                                <span class="fs-8 text-muted">@lang('lang_v1.custom_labels')</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 col-xxl-8">
        <div id="business-settings-panes">
            @include('business.partials.settings_business')
            @include('business.partials.settings_tax')
            @include('business.partials.settings_product')
            @include('business.partials.settings_contact')
            @include('business.partials.settings_sales')
            @include('business.partials.settings_pos')
            @include('business.partials.settings_display_pos')
            @include('business.partials.settings_purchase')
            @include('business.partials.settings_payment')
            @include('business.partials.settings_dashboard')
            @include('business.partials.settings_system')
            @include('business.partials.settings_prefixes')
            @include('business.partials.settings_email')
            @include('business.partials.settings_sms')
            @include('business.partials.settings_reward_point')
            @include('business.partials.settings_modules')
            @include('business.partials.settings_custom_labels')
        </div>
    </div>
</div>
{!! Form::close() !!}
@stop

@section('javascript')
<script type="text/javascript">
    __page_leave_confirmation('#bussiness_edit_form');

    $(document).on('ifToggled', '#use_superadmin_settings', function() {
        if ($('#use_superadmin_settings').is(':checked')) {
            $('#toggle_visibility').addClass('hide');
            $('.test_email_btn').addClass('hide');
        } else {
            $('#toggle_visibility').removeClass('hide');
            $('.test_email_btn').removeClass('hide');
        }
    });

    $(document).ready(function() {
        var applyMetronicFormStyles = function(scopeSelector) {
            var $scope = $(scopeSelector);
            if (!$scope.length) {
                return;
            }

            $scope.find('.form-group').addClass('mb-7');
            $scope.find('label').not('.form-check-label').addClass('form-label fw-semibold text-gray-700 fs-6 mb-2');

            $scope.find('input.form-control, textarea.form-control').addClass('form-control-solid');
            $scope.find('textarea:not(.form-control)').addClass('form-control form-control-solid');

            $scope.find('select.form-control').each(function() {
                var $select = $(this);
                if ($select.closest('.input-group').length) {
                    $select.addClass('form-control-solid');
                    return;
                }
                $select.removeClass('form-control').addClass('form-select form-select-solid');
            });

            $scope.find('select:not(.form-select):not(.select2-hidden-accessible)').each(function() {
                var $select = $(this);
                if ($select.closest('.input-group').length) {
                    $select.addClass('form-control form-control-solid');
                    return;
                }
                $select.addClass('form-select form-select-solid');
            });

            $scope.find('.input-group').addClass('input-group-solid');
            $scope.find('.input-group-addon').addClass('input-group-text');
            $scope.find('.help-block').addClass('text-muted fs-7');

            $scope.find('.checkbox, .radio').each(function() {
                var $wrapper = $(this);
                $wrapper.addClass('form-check form-check-custom form-check-solid');
                $wrapper.find('input[type="checkbox"], input[type="radio"]').first().addClass('form-check-input');
                $wrapper.find('label').first().addClass('form-check-label fw-semibold text-gray-700');
            });

            $scope.find('input.input-icheck').addClass('form-check-input');
            $scope.find('span.input-group-btn .btn').addClass('btn btn-light-primary');
            $scope.find('input.start-date-picker').addClass('form-control-solid');
        };

        applyMetronicFormStyles('#business-settings-panes');

        var $settingsTabs = $('#business-settings-tabs');
        var $navLinks = $settingsTabs.find('.business-settings-nav .nav-link');
        var $tabPanes = $('#business-settings-panes').children('.pos-tab-content');

        var activateBusinessSettingsTab = function(index) {
            $navLinks.removeClass('active');
            $navLinks.eq(index).addClass('active');

            $tabPanes.removeClass('active').addClass('d-none');
            $tabPanes.eq(index).addClass('active').removeClass('d-none');
        };

        if ($navLinks.length && $tabPanes.length) {
            activateBusinessSettingsTab(0);
        }

        $settingsTabs.on('click', '.business-settings-nav .nav-link', function(e) {
            e.preventDefault();
            activateBusinessSettingsTab($(this).closest('.nav-item').index());
        });

        $('#test_email_btn').click(function() {
            var data = {
                mail_driver: $('#mail_driver').val(),
                mail_host: $('#mail_host').val(),
                mail_port: $('#mail_port').val(),
                mail_username: $('#mail_username').val(),
                mail_password: $('#mail_password').val(),
                mail_encryption: $('#mail_encryption').val(),
                mail_from_address: $('#mail_from_address').val(),
                mail_from_name: $('#mail_from_name').val(),
            };

            $.ajax({
                method: 'post',
                data: data,
                url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testEmailConfiguration']) }}",
                dataType: 'json',
                success: function(result) {
                    if (result.success == true) {
                        swal({
                            text: result.msg,
                            icon: 'success'
                        });
                    } else {
                        swal({
                            text: result.msg,
                            icon: 'error'
                        });
                    }
                },
            });
        });

        $('#test_sms_btn').click(function() {
            var test_number = $('#test_number').val();
            if (test_number.trim() == '') {
                toastr.error('{{__("lang_v1.test_number_is_required")}}');
                $('#test_number').focus();

                return false;
            }

            var data = {
                url: $('#sms_settings_url').val(),
                send_to_param_name: $('#send_to_param_name').val(),
                msg_param_name: $('#msg_param_name').val(),
                request_method: $('#request_method').val(),
                param_1: $('#sms_settings_param_key1').val(),
                param_2: $('#sms_settings_param_key2').val(),
                param_3: $('#sms_settings_param_key3').val(),
                param_4: $('#sms_settings_param_key4').val(),
                param_5: $('#sms_settings_param_key5').val(),
                param_6: $('#sms_settings_param_key6').val(),
                param_7: $('#sms_settings_param_key7').val(),
                param_8: $('#sms_settings_param_key8').val(),
                param_9: $('#sms_settings_param_key9').val(),
                param_10: $('#sms_settings_param_key10').val(),

                param_val_1: $('#sms_settings_param_val1').val(),
                param_val_2: $('#sms_settings_param_val2').val(),
                param_val_3: $('#sms_settings_param_val3').val(),
                param_val_4: $('#sms_settings_param_val4').val(),
                param_val_5: $('#sms_settings_param_val5').val(),
                param_val_6: $('#sms_settings_param_val6').val(),
                param_val_7: $('#sms_settings_param_val7').val(),
                param_val_8: $('#sms_settings_param_val8').val(),
                param_val_9: $('#sms_settings_param_val9').val(),
                param_val_10: $('#sms_settings_param_val10').val(),
                test_number: test_number,

                header_1: $('#sms_settings_header_key1').val(),
                header_val_1: $('#sms_settings_header_val1').val(),
                header_2: $('#sms_settings_header_key2').val(),
                header_val_2: $('#sms_settings_header_val2').val(),
                header_3: $('#sms_settings_header_key3').val(),
                header_val_3: $('#sms_settings_header_val3').val(),
                data_parameter_type: $('#data_parameter_type').val(),
            };

            $.ajax({
                method: 'post',
                data: data,
                url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testSmsConfiguration']) }}",
                dataType: 'json',
                success: function(result) {
                    if (result.success == true) {
                        swal({
                            text: result.msg,
                            icon: 'success'
                        });
                    } else {
                        swal({
                            text: result.msg,
                            icon: 'error'
                        });
                    }
                },
            });
        });

        $('select.custom_labels_products').change(function() {
            value = $(this).val();
            textarea = $(this).parents('div.custom_label_product_div').find('div.custom_label_product_dropdown');
            if (value == 'dropdown') {
                textarea.removeClass('hide');
            } else {
                textarea.addClass('hide');
            }
        });

        tinymce.init({
            selector: 'textarea#display_screen_heading',
            height: 250
        });

        $('.carousel_image').fileinput({
            showUpload: true,
            showPreview: true,
            browseLabel: LANG.file_browse_label,
            removeLabel: LANG.remove,
        });
    });
</script>
@endsection

