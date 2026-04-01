@extends('layouts.app')

@section('title', __('lang_v1.view_user'))

@php
    $active_tab = $active_tab ?? request()->query('tab', 'overview');
    $valid_tabs = ['overview', 'settings', 'documents', 'activities'];
    if (! in_array($active_tab, $valid_tabs, true)) {
        $active_tab = 'overview';
    }
    if ($active_tab === 'settings' && ! auth()->user()->can('user.update')) {
        $active_tab = 'overview';
    }

    if (isset($user->media->display_url)) {
        $img_src = $user->media->display_url;
    } else {
        $img_src = 'https://ui-avatars.com/api/?name=' . urlencode($user->first_name ?? 'User');
    }
@endphp

@section('content')
    <div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">@lang('lang_v1.view_user')</h1>
                <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                    <li class="breadcrumb-item text-gray-600">
                        <a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a>
                    </li>
                    <li class="breadcrumb-item text-gray-600">
                        <a href="{{ url('/users') }}" class="text-gray-600 text-hover-primary">@lang('user.users')</a>
                    </li>
                    <li class="breadcrumb-item text-gray-500">{{ $user->user_full_name }}</li>
                </ul>
            </div>

            <div class="d-flex align-items-center py-2">
                {!! Form::select(
                    'user_id',
                    $users,
                    $user->id,
                    [
                        'class' => 'form-select form-select-solid w-200px',
                        'id' => 'user_id',
                    ]
                ) !!}
            </div>
        </div>
    </div>

    <div class="d-flex flex-column-fluid align-items-start container-xxl">
        <div class="content flex-row-fluid" id="kt_content">
            <div class="card mb-5 mb-xl-10">
                <div class="card-body pt-9 pb-0">
                    <div class="d-flex flex-wrap flex-sm-nowrap">
                        <div class="me-7 mb-4">
                            <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                                <img src="{{ $img_src }}" alt="User profile picture">
                                <div class="position-absolute translate-middle bottom-0 start-100 mb-6 rounded-circle border border-4 border-body h-20px w-20px {{ $user->status == 'active' ? 'bg-success' : 'bg-danger' }}"></div>
                            </div>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="text-gray-900 fs-2 fw-bold me-2">{{ $user->user_full_name }}</span>
                                        <span class="badge {{ $user->status == 'active' ? 'badge-light-success' : 'badge-light-danger' }}">
                                            {{ $user->status == 'active' ? __('business.is_active') : __('lang_v1.inactive') }}
                                        </span>
                                    </div>
                                    <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                        <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                            <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                            </i>
                                            {{ $user->role_name }}
                                        </span>
                                        @if (! empty($user->username))
                                            <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                                <i class="ki-duotone ki-security-user fs-4 me-1">
                                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                                </i>
                                                {{ $user->username }}
                                            </span>
                                        @endif
                                        @if (! empty($user->email))
                                            <span class="d-flex align-items-center text-gray-500 mb-2">
                                                <i class="ki-duotone ki-sms fs-4 me-1">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                                {{ $user->email }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="d-flex my-4">
                                    @can('user.update')
                                        <a href="{{ route('users.show', ['user' => $user->id, 'tab' => 'settings']) }}" class="btn btn-sm btn-light-primary me-2">
                                            <i class="ki-duotone ki-pencil fs-4">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            @lang('messages.edit')
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div class="d-flex flex-wrap flex-stack">
                                <div class="d-flex flex-column flex-grow-1 pe-8">
                                    <div class="d-flex flex-wrap">
                                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="ki-duotone ki-user fs-3 text-primary me-2">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                                <div class="fs-2 fw-bold">{{ $user->username ?? '-' }}</div>
                                            </div>
                                            <div class="fw-semibold fs-6 text-gray-500">@lang('business.username')</div>
                                        </div>

                                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="ki-duotone ki-sms fs-3 text-danger me-2">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                                <div class="fs-2 fw-bold">{{ $user->email ?? '-' }}</div>
                                            </div>
                                            <div class="fw-semibold fs-6 text-gray-500">@lang('business.email')</div>
                                        </div>

                                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="ki-duotone ki-shield-tick fs-3 text-success me-2">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                                <div class="fs-2 fw-bold">{{ $user->status == 'active' ? __('business.is_active') : __('lang_v1.inactive') }}</div>
                                            </div>
                                            <div class="fw-semibold fs-6 text-gray-500">@lang('lang_v1.status_for_user')</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                        <li class="nav-item mt-2">
                            <a
                                class="nav-link text-active-primary ms-0 me-10 py-5 {{ $active_tab === 'overview' ? 'active' : '' }}"
                                href="#tab_user_info"
                                data-bs-toggle="tab"
                                data-bs-target="#tab_user_info"
                            >
                                @lang('lang_v1.user_info')
                            </a>
                        </li>

                        @can('user.update')
                            <li class="nav-item mt-2">
                                <a
                                    class="nav-link text-active-primary ms-0 me-10 py-5 {{ $active_tab === 'settings' ? 'active' : '' }}"
                                    href="#tab_user_settings"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab_user_settings"
                                >
                                    @lang('messages.settings')
                                </a>
                            </li>
                        @endcan

                        <li class="nav-item mt-2">
                            <a
                                class="nav-link text-active-primary ms-0 me-10 py-5 {{ $active_tab === 'documents' ? 'active' : '' }}"
                                href="#tab_documents_and_notes"
                                data-bs-toggle="tab"
                                data-bs-target="#tab_documents_and_notes"
                            >
                                @lang('lang_v1.documents_and_notes')
                            </a>
                        </li>

                        <li class="nav-item mt-2">
                            <a
                                class="nav-link text-active-primary ms-0 me-10 py-5 {{ $active_tab === 'activities' ? 'active' : '' }}"
                                href="#tab_activities"
                                data-bs-toggle="tab"
                                data-bs-target="#tab_activities"
                            >
                                @lang('lang_v1.activities')
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade {{ $active_tab === 'overview' ? 'show active' : '' }}" id="tab_user_info">
                    <div class="card mb-5 mb-xl-10" id="kt_profile_details_view">
                        <div class="card-header cursor-pointer">
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">@lang('lang_v1.user_info')</h3>
                            </div>
                            @can('user.update')
                                <a href="{{ route('users.show', ['user' => $user->id, 'tab' => 'settings']) }}" class="btn btn-sm btn-primary align-self-center">
                                    @lang('messages.edit')
                                </a>
                            @endcan
                        </div>

                        <div class="card-body p-9">
                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.cmmsn_percent')</label>
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $user->cmmsn_percent }}%</span>
                                </div>
                            </div>

                            <div class="row mb-7">
                                <label class="col-lg-4 fw-semibold text-muted">@lang('lang_v1.allowed_contacts')</label>
                                <div class="col-lg-8 fv-row">
                                    @php
                                        $selected_contacts = [];
                                        if (count($user->contactAccess)) {
                                            foreach ($user->contactAccess as $contact) {
                                                $selected_contacts[] = $contact->name;
                                            }
                                        }
                                    @endphp
                                    <span class="fw-semibold text-gray-800 fs-6">
                                        {{ ! empty($selected_contacts) ? implode(', ', $selected_contacts) : __('lang_v1.all') }}
                                    </span>
                                </div>
                            </div>

                            @include('user.show_details')
                        </div>
                    </div>
                </div>

                @can('user.update')
                    <div class="tab-pane fade {{ $active_tab === 'settings' ? 'show active' : '' }}" id="tab_user_settings">
                        <div class="card mb-5 mb-xl-10">
                            <div class="card-header cursor-pointer">
                                <div class="card-title m-0">
                                    <h3 class="fw-bold m-0">@lang('messages.settings')</h3>
                                </div>
                            </div>
                            <div class="card-body p-9">
                                @includeIf('manage_user.partials.settings_form')
                            </div>
                        </div>
                    </div>
                @endcan

                <div class="tab-pane fade {{ $active_tab === 'documents' ? 'show active' : '' }}" id="tab_documents_and_notes">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body p-9">
                            <input type="hidden" name="notable_id" id="notable_id" value="{{ $user->id }}">
                            <input type="hidden" name="notable_type" id="notable_type" value="App\User">
                            <div class="document_note_body"></div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $active_tab === 'activities' ? 'show active' : '' }}" id="tab_activities">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-body p-9">
                            @include('activity_log.activities')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('documents_and_notes.document_and_note_js')
    @can('user.update')
        @includeIf('manage_user.partials.settings_form_js')
    @endcan

    <script type="text/javascript">
        $(document).ready(function () {
            var activeTab = @json($active_tab);
            var tabMap = {
                overview: '#tab_user_info',
                settings: '#tab_user_settings',
                documents: '#tab_documents_and_notes',
                activities: '#tab_activities',
            };

            if (tabMap[activeTab]) {
                var trigger = document.querySelector('[data-bs-target="' + tabMap[activeTab] + '"]');
                if (trigger) {
                    new bootstrap.Tab(trigger).show();
                }
            }

            var getCurrentTabKey = function () {
                var target = $('.nav-link.active[data-bs-toggle="tab"]').data('bs-target');
                switch (target) {
                    case '#tab_user_settings':
                        return 'settings';
                    case '#tab_documents_and_notes':
                        return 'documents';
                    case '#tab_activities':
                        return 'activities';
                    case '#tab_user_info':
                    default:
                        return 'overview';
                }
            };

            $('#user_id').change(function () {
                if ($(this).val()) {
                    window.location = "{{ url('/users') }}/" + $(this).val() + '?tab=' + getCurrentTabKey();
                }
            });
        });
    </script>
@endsection
