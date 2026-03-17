@extends('projectx::site_manager.layouts.public')

@section('title', 'Services - ' . $siteName)

@section('content')
    <div class="mb-n10 mb-lg-n20 z-index-2 mt-20">
        <div class="container">
            <div class="text-center mb-17">
                <h3 class="fs-2hx text-gray-900 mb-5">{{ $servicesHeading }}</h3>
                <div class="fs-5 text-muted fw-bold">{{ $servicesSubtitle }}</div>
            </div>
            <div class="row w-100 gy-10 mb-md-20">
                @foreach ($services as $service)
                    <div class="col-md-4 px-5">
                        <div class="text-center mb-10 mb-md-0">
                            <img src="{{ $service['image'] }}" class="mh-125px mb-9" alt="" />
                            <div class="d-flex flex-center mb-5">
                                <span class="badge badge-circle badge-light-success fw-bold p-5 me-3 fs-3">{{ $service['badge'] }}</span>
                                <div class="fs-5 fs-lg-3 fw-bold text-gray-900">{{ $service['title'] }}</div>
                            </div>
                            <div class="fw-semibold fs-6 fs-lg-4 text-muted">{{ $service['description'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
