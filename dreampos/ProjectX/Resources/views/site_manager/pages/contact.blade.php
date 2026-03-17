@extends('projectx::site_manager.layouts.public')

@section('title', 'Contact - ' . $siteName)

@section('content')
    <div class="container-xxl mt-20 mb-20">
        @if (session('status') && is_array(session('status')) && !empty(session('status.success')))
            <div class="alert alert-success d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <div class="d-flex flex-column">
                    <span>{{ session('status.msg') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-information fs-2hx text-danger me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="d-flex flex-column">
                    @foreach ($errors->all() as $error)
                        <span>{{ $error }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body p-lg-17">
                <div class="row mb-3">
                    <div class="col-md-6 pe-lg-10">
                        <form action="{{ route('public.contact.submit') }}" class="form mb-15" method="post" id="kt_contact_form">
                            @csrf
                            <h1 class="fw-bold text-gray-900 mb-9">{{ $contactHeading }}</h1>
                            <div class="row mb-5">
                                <div class="col-md-6 fv-row">
                                    <label class="fs-5 fw-semibold mb-2">Name</label>
                                    <input type="text" class="form-control form-control-solid" name="name" value="{{ old('name') }}" />
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="fs-5 fw-semibold mb-2">Email</label>
                                    <input type="text" class="form-control form-control-solid" name="email" value="{{ old('email') }}" />
                                </div>
                            </div>
                            <div class="d-flex flex-column mb-5 fv-row">
                                <label class="fs-5 fw-semibold mb-2">Phone</label>
                                <input class="form-control form-control-solid" name="phone" value="{{ old('phone') }}" />
                            </div>
                            <div class="d-flex flex-column mb-10 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Message</label>
                                <textarea class="form-control form-control-solid" rows="6" name="message">{{ old('message') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" id="kt_contact_submit_button">
                                <span class="indicator-label">Send Feedback</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 ps-lg-10">
                        <div id="kt_contact_map" class="w-100 rounded mb-2 mb-lg-0 mt-2" style="height: 486px"></div>
                    </div>
                </div>
                <div class="row g-5 mb-5 mb-lg-15">
                    <div class="col-sm-6 pe-lg-10">
                        <div class="bg-light card-rounded d-flex flex-column flex-center flex-center p-10 h-100">
                            <i class="ki-duotone ki-briefcase fs-3tx text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h1 class="text-gray-900 fw-bold my-5">Let's Speak</h1>
                            <div class="text-gray-700 fw-semibold fs-2">{{ $contactPhone }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6 ps-lg-10">
                        <div class="text-center bg-light card-rounded d-flex flex-column flex-center p-10 h-100">
                            <i class="ki-duotone ki-geolocation fs-3tx text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h1 class="text-gray-900 fw-bold my-5">Our Head Office</h1>
                            <div class="text-gray-700 fs-3 fw-semibold">{{ $contactAddress }}</div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4 bg-light text-center">
                    <div class="card-body py-12">
                        @foreach ($contactSocialLinks as $link)
                            <a href="{{ $link['url'] }}" class="mx-4">
                                <img src="{{ $link['icon'] }}" class="h-30px my-2" alt="" />
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
