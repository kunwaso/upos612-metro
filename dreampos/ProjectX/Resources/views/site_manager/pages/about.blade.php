@extends('projectx::site_manager.layouts.public')

@section('title', 'About - ' . $siteName)

@section('content')
    <div class="mt-sm-n10">
        <div class="landing-curve landing-dark-color">
            <svg viewBox="15 -1 1470 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 48C4.93573 47.6644 8.85984 47.3311 12.7725 47H1489.16C1493.1 47.3311 1497.04 47.6644 1501 48V47H1489.16C914.668 -1.34764 587.282 -1.61174 12.7725 47H1V48Z" fill="currentColor"></path>
            </svg>
        </div>
        <div class="pb-15 pt-18 landing-dark-bg">
            <div class="container">
                <div class="text-center mt-15 mb-18">
                    <h3 class="fs-2hx text-white fw-bold mb-5">{{ $aboutHeading }}</h3>
                    <div class="fs-5 text-gray-700 fw-bold">{{ $aboutSubtitle }}</div>
                </div>
                <div class="row g-lg-10 mb-10 mb-lg-20">
                    <div class="col-lg-6">
                        <div class="rounded landing-dark-border p-9 mb-10">
                            <h2 class="text-white">Our Mission</h2>
                            <span class="fw-normal fs-4 text-gray-700">{{ $aboutBody }}</span>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="rounded landing-dark-border p-9">
                            <h2 class="text-white">Our Direction</h2>
                            <span class="fw-normal fs-4 text-gray-700">Use one connected platform to simplify delivery, visibility, and collaboration for every team and every project milestone.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
