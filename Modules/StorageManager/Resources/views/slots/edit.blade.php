@extends('layouts.app')

@section('title', __('lang_v1.edit_storage_slot'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    @lang('lang_v1.edit_storage_slot')
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('storage-manager.slots.index') }}" class="text-muted text-hover-primary">@lang('lang_v1.storage_slots')</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.edit_storage_slot')</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.edit_storage_slot')</h3>
                </div>
                <div class="card-body pt-4">
                    <form method="POST" action="{{ route('storage-manager.slots.update', $slot->id) }}">
                        @csrf
                        @method('PUT')
                        @include('storagemanager::slots._form')
                        <div class="d-flex justify-content-end mt-6">
                            <a href="{{ route('storage-manager.slots.index') }}" class="btn btn-light me-3">@lang('messages.cancel')</a>
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
