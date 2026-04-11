@extends('layouts.app')

@section('title', __('lang_v1.add_storage_slot'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <x-storagemanager::storage-toolbar
        :title="$storageToolbarTitle"
        :breadcrumbs="$storageToolbarBreadcrumbs"
        :map-location-id="$storageToolbarLocationId ?? null"
    />

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.add_storage_slot')</h3>
                </div>
                <div class="card-body pt-4">
                    <form method="POST" action="{{ route('storage-manager.slots.store') }}">
                        @csrf
                        @include('storagemanager::slots._form')
                        <div class="d-flex justify-content-end mt-6">
                            <a href="{{ route('storage-manager.slots.index') }}" class="btn btn-light me-3">@lang('messages.cancel')</a>
                            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
    @stack('javascript')
@endsection
