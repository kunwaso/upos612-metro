@extends('layouts.app')
@section('title', __('lang_v1.my_profile'))

@section('content')
@php
    $bank_details = !empty($user->bank_details) ? json_decode($user->bank_details, true) : [];
    $user_full_name = trim($user->user_full_name ?? '');
    if ($user_full_name === '') {
        $user_full_name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
    }

    $profile_completion_checks = [
        !empty($user->first_name),
        !empty($user->last_name),
        !empty($user->email),
        !empty($user->contact_number),
        !empty($user->language),
        !empty($user->dob),
        !empty($user->current_address),
        !empty($user->permanent_address),
        !empty($user->id_proof_number),
        !empty($bank_details['account_number']),
        !empty($bank_details['bank_name']),
        !empty($user->media),
    ];

    $profile_completion_total = count($profile_completion_checks);
    $profile_completion_done = collect($profile_completion_checks)->filter()->count();
    $profile_completion_percent = (int) round(($profile_completion_done / max($profile_completion_total, 1)) * 100);

    $active_profile_tab = request()->query('tab', 'overview');
    if (!in_array($active_profile_tab, ['overview', 'edit', 'password'], true)) {
        $active_profile_tab = 'overview';
    }

    $user_status_label = $user->status == 'active' ? __('business.is_active') : __('lang_v1.inactive');
@endphp

<div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
        <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
            <div class="me-7 mb-4">
                <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                    <img src="{{ $user->image_url }}" alt="{{ $user_full_name }}">
                </div>
            </div>

            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-gray-900 fs-2 fw-bold me-2">{{ $user_full_name ?: '-' }}</span>
                            @if(!empty($user->role_name))
                                <span class="badge badge-light-success fw-bold fs-8 px-2 py-1">{{ $user->role_name }}</span>
                            @endif
                        </div>

                        <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                            <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                <i class="ki-duotone ki-sms fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ $user->email ?: '-' }}
                            </span>
                            <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                <i class="ki-duotone ki-shield-tick fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ $user_status_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap flex-stack">
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <div class="d-flex flex-wrap">
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-sms fs-3 text-primary me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="fs-5 fw-bold text-gray-900">{{ $user->email ?: '-' }}</div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">@lang('business.email')</div>
                            </div>

                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-shield-tick fs-3 text-success me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="fs-5 fw-bold text-gray-900">{{ $user_status_label }}</div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">@lang('lang_v1.status_for_user')</div>
                            </div>

                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-3 text-info me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                    <div class="fs-5 fw-bold text-gray-900">{{ $profile_completion_percent }}%</div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">@lang('lang_v1.profile')</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center w-200px w-sm-250px flex-column mt-3">
                        <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                            <span class="fw-semibold fs-7 text-gray-500">@lang('lang_v1.profile')</span>
                            <span class="fw-bold fs-7 text-gray-900">{{ $profile_completion_percent }}%</span>
                        </div>
                        <div class="h-5px mx-3 w-100 bg-light mb-3">
                            <div class="bg-success rounded h-5px" role="progressbar" style="width: {{ $profile_completion_percent }}%;" aria-valuenow="{{ $profile_completion_percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $active_profile_tab === 'overview' ? 'active' : '' }}"
                   href="#kt_user_profile_tab_overview"
                   data-bs-toggle="tab"
                   data-bs-target="#kt_user_profile_tab_overview"
                   data-profile-tab="overview">
                    @lang('product.overview')
                </a>
            </li>
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $active_profile_tab === 'edit' ? 'active' : '' }}"
                   href="#kt_user_profile_tab_edit"
                   data-bs-toggle="tab"
                   data-bs-target="#kt_user_profile_tab_edit"
                   data-profile-tab="edit">
                    @lang('user.edit_profile')
                </a>
            </li>
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $active_profile_tab === 'password' ? 'active' : '' }}"
                   href="#kt_user_profile_tab_password"
                   data-bs-toggle="tab"
                   data-bs-target="#kt_user_profile_tab_password"
                   data-profile-tab="password">
                    @lang('user.change_password')
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">
    <div class="tab-pane fade {{ $active_profile_tab === 'overview' ? 'show active' : '' }}" id="kt_user_profile_tab_overview">
        <div class="card mb-5 mb-xl-10" id="kt_profile_details_view">
            <div class="card-header cursor-pointer">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">@lang('lang_v1.profile')</h3>
                </div>
                <a href="#kt_user_profile_tab_edit"
                   class="btn btn-sm btn-primary align-self-center"
                   data-bs-toggle="tab"
                   data-bs-target="#kt_user_profile_tab_edit"
                   data-profile-tab="edit">
                    @lang('user.edit_profile')
                </a>
            </div>

            <div class="card-body p-9">
                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.name')</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ $user_full_name ?: '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('business.email')</label>
                    <div class="col-lg-8 fv-row">
                        <span class="fw-semibold text-gray-800 fs-6">{{ $user->email ?: '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('business.language')</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ $languages[$user->language] ?? ($user->language ?: '-') }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.mobile_number')</label>
                    <div class="col-lg-8 d-flex align-items-center">
                        <span class="fw-bold fs-6 text-gray-800 me-2">{{ $user->contact_number ?: '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('business.alternate_number')</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ $user->alt_number ?: '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.dob')</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ !empty($user->dob) ? format_date_value($user->dob) : '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.gender')</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ !empty($user->gender) ? __('lang_v1.'.$user->gender) : '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.marital_status')</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ !empty($user->marital_status) ? __('lang_v1.'.$user->marital_status) : '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.current_address')</label>
                    <div class="col-lg-8">
                        <span class="fw-semibold fs-6 text-gray-800">{{ $user->current_address ?: '-' }}</span>
                    </div>
                </div>

                <div class="row mb-7">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.permanent_address')</label>
                    <div class="col-lg-8">
                        <span class="fw-semibold fs-6 text-gray-800">{{ $user->permanent_address ?: '-' }}</span>
                    </div>
                </div>

                <div class="row mb-0">
                    <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.id_proof_number')</label>
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">{{ $user->id_proof_number ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade {{ $active_profile_tab === 'edit' ? 'show active' : '' }}" id="kt_user_profile_tab_edit">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\UserController::class, 'updateProfile']),
            'method' => 'post',
            'id' => 'edit_user_profile_form',
            'files' => true,
        ]) !!}

        <div class="card mb-5 mb-xl-10">
            <div class="card-header cursor-pointer">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">@lang('user.edit_profile')</h3>
                </div>
            </div>
            <div class="card-body p-9">
                <div class="row g-5 mb-1">
                    <div class="col-md-2">
                        {!! Form::label('surname', __('business.prefix') . ':', ['class' => 'form-label']) !!}
                        {!! Form::text('surname', $user->surname, ['class' => 'form-control form-control-solid', 'placeholder' => __('business.prefix_placeholder')]) !!}
                    </div>
                    <div class="col-md-5">
                        {!! Form::label('first_name', __('business.first_name') . ':', ['class' => 'form-label']) !!}
                        {!! Form::text('first_name', $user->first_name, ['class' => 'form-control form-control-solid', 'placeholder' => __('business.first_name'), 'required']) !!}
                    </div>
                    <div class="col-md-5">
                        {!! Form::label('last_name', __('business.last_name') . ':', ['class' => 'form-label']) !!}
                        {!! Form::text('last_name', $user->last_name, ['class' => 'form-control form-control-solid', 'placeholder' => __('business.last_name')]) !!}
                    </div>
                    <div class="col-md-6">
                        {!! Form::label('email', __('business.email') . ':', ['class' => 'form-label']) !!}
                        {!! Form::email('email', $user->email, ['class' => 'form-control form-control-solid', 'placeholder' => __('business.email')]) !!}
                    </div>
                    <div class="col-md-6">
                        {!! Form::label('language', __('business.language') . ':', ['class' => 'form-label']) !!}
                        {!! Form::select('language', $languages, $user->language, ['class' => 'form-select form-select-solid select2', 'id' => 'profile_language']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-5 mb-xl-10">
            <div class="card-header cursor-pointer">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">@lang('lang_v1.profile_photo')</h3>
                </div>
            </div>
            <div class="card-body p-9">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-6">
                    <div class="flex-shrink-0">
                        <img src="{{ $user->image_url }}" alt="{{ $user_full_name }}" class="rounded w-150px h-150px" style="object-fit: cover;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="mb-3">
                            {!! Form::label('profile_photo', __('lang_v1.upload_image') . ':', ['class' => 'form-label']) !!}
                            {!! Form::file('profile_photo', ['id' => 'profile_photo', 'accept' => 'image/*', 'class' => 'form-control form-control-solid']) !!}
                        </div>
                        <div class="text-muted fs-7">
                            @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-5 mb-xl-10">
            <div class="card-header cursor-pointer">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">@lang('lang_v1.more_info')</h3>
                </div>
            </div>
            <div class="card-body p-9">
                @include('user.form', ['bank_details' => $bank_details, 'section' => 'more'])
            </div>
        </div>

        <div class="card mb-5 mb-xl-10">
            <div class="card-header cursor-pointer">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">@lang('lang_v1.bank_details')</h3>
                </div>
            </div>
            <div class="card-body p-9">
                @include('user.form', ['bank_details' => $bank_details, 'section' => 'bank'])
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
        </div>
        {!! Form::close() !!}
    </div>

    <div class="tab-pane fade {{ $active_profile_tab === 'password' ? 'show active' : '' }}" id="kt_user_profile_tab_password">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\UserController::class, 'updatePassword']),
            'method' => 'post',
            'id' => 'edit_password_form',
            'class' => 'form-horizontal',
        ]) !!}

        <div class="card mb-5 mb-xl-10">
            <div class="card-header cursor-pointer">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">@lang('user.change_password')</h3>
                </div>
            </div>
            <div class="card-body p-9">
                <div class="row mb-6">
                    {!! Form::label('current_password', __('user.current_password') . ':', ['class' => 'col-lg-4 col-form-label fw-semibold fs-6']) !!}
                    <div class="col-lg-8 fv-row">
                        {!! Form::password('current_password', ['class' => 'form-control form-control-solid', 'placeholder' => __('user.current_password'), 'required']) !!}
                    </div>
                </div>

                <div class="row mb-6">
                    {!! Form::label('new_password', __('user.new_password') . ':', ['class' => 'col-lg-4 col-form-label fw-semibold fs-6']) !!}
                    <div class="col-lg-8 fv-row">
                        {!! Form::password('new_password', ['id' => 'new_password', 'class' => 'form-control form-control-solid', 'placeholder' => __('user.new_password'), 'required']) !!}
                    </div>
                </div>

                <div class="row mb-6">
                    {!! Form::label('confirm_password', __('user.confirm_new_password') . ':', ['class' => 'col-lg-4 col-form-label fw-semibold fs-6']) !!}
                    <div class="col-lg-8 fv-row">
                        {!! Form::password('confirm_password', ['class' => 'form-control form-control-solid', 'placeholder' => __('user.confirm_new_password'), 'required']) !!}
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var tabStorageKey = 'upos_user_profile_active_tab';
        var queryTab = @json(request()->query('tab'));
        var initialTab = queryTab || localStorage.getItem(tabStorageKey) || 'overview';
        var initialTrigger = document.querySelector('[data-profile-tab="' + initialTab + '"]');

        if (initialTrigger) {
            new bootstrap.Tab(initialTrigger).show();
        }

        $('a[data-bs-toggle="tab"][data-profile-tab]').on('shown.bs.tab', function (event) {
            var activeTab = $(event.target).data('profile-tab');
            localStorage.setItem(tabStorageKey, activeTab);

            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', activeTab);
            window.history.replaceState({}, '', currentUrl.toString());

            if (activeTab === 'edit') {
                $('#edit_user_profile_form .select2').each(function () {
                    var $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.trigger('change.select2');
                    } else if (typeof __select2 === 'function') {
                        __select2($select);
                    }
                });
            }
        });
    });
</script>
@endsection
