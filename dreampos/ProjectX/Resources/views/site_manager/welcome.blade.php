@extends('projectx::site_manager.layouts.public')

@section('title', $siteName)

@section('content')
    <div class="mb-0" id="home">
        <div class="bgi-no-repeat bgi-size-contain bgi-position-x-center bgi-position-y-bottom landing-dark-bg" style="background-image: url('{{ asset('modules/projectx/media/svg/illustrations/landing.svg') }}')">
            <div class="d-flex flex-column flex-center w-100 min-h-350px min-h-lg-500px px-9">
                <div class="text-center mb-5 mb-lg-10 py-10 py-lg-20">
                    <h1 class="text-white lh-base fw-bold fs-2x fs-lg-3x mb-15">
                        {{ $heroTitle }}
                    </h1>
                    @if (!empty($heroSubtitle))
                        <div class="fs-5 text-gray-700 fw-bold mb-10">{{ $heroSubtitle }}</div>
                    @endif
                    <a href="{{ $ctaUrl }}" class="btn btn-primary">{{ $ctaLabel }}</a>
                </div>
                <div class="d-flex flex-center flex-wrap position-relative px-5">
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Fujifilm">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/fujifilm.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Vodafone">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/vodafone.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="KPMG International">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/kpmg.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Nasa">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/nasa.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Aspnetzero">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/aspnetzero.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="AON - Empower Results">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/aon.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Hewlett-Packard">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/hp-3.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                    <div class="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Truman">
                        <img src="{{ asset('modules/projectx/media/svg/brand-logos/truman.svg') }}" class="mh-30px mh-lg-40px" alt="" />
                    </div>
                </div>
            </div>
        </div>
        <div class="landing-curve landing-dark-color mb-10 mb-lg-20">
            <svg viewBox="15 12 1470 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 11C3.93573 11.3356 7.85984 11.6689 11.7725 12H1488.16C1492.1 11.6689 1496.04 11.3356 1500 11V12H1488.16C913.668 60.3476 586.282 60.6117 11.7725 12H0V11Z" fill="currentColor"></path>
            </svg>
        </div>
    </div>
@endsection
