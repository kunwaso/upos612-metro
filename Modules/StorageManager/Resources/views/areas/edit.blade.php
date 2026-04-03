@extends('layouts.app')

@section('title', __('lang_v1.edit_warehouse_area'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">@lang('lang_v1.edit_warehouse_area')</h1>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-body">
                    <form method="POST" action="{{ route('storage-manager.areas.update', $area->id) }}">
                        @csrf
                        @method('PUT')
                        @include('storagemanager::areas._form')
                        <div class="d-flex justify-content-end mt-6">
                            <a href="{{ route('storage-manager.areas.index') }}" class="btn btn-light me-3">@lang('messages.cancel')</a>
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
