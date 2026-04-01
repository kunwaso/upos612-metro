@extends('layouts.app')

@section('title', __( 'user.edit_user' ))

@section('content')

{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">@lang('user.edit_user')</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">@lang('user.edit_user')</li>
            </ul>
        </div>
    </div>
</div>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        @include('manage_user.partials.settings_form')
    </div>
</div>

@endsection

@section('javascript')
    @include('manage_user.partials.settings_form_js')
@endsection
