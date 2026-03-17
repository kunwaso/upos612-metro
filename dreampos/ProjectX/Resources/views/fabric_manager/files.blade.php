@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_files'))

@section('content')
@include('projectx::fabric_manager._fabric_header')

<div class="d-flex flex-wrap flex-stack pt-10 pb-8">
    <h3 class="fw-bold my-2">{{ __('projectx::lang.fabric_files') }}
        <span class="fs-6 text-gray-500 fw-semibold ms-1">{{ __('projectx::lang.resources_count', ['count' => '100+']) }}</span>
    </h3>
    <div class="d-flex flex-wrap my-1">
        <div class="d-flex align-items-center position-relative my-1 me-4">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3"><span class="path1"></span><span class="path2"></span></i>
            <input type="text" class="form-control form-control-sm form-control-solid w-150px ps-10" placeholder="{{ __('projectx::lang.search') }}..." />
        </div>
        <a href="#" class="btn btn-primary btn-sm">{{ __('projectx::lang.file_manager') }}</a>
    </div>
</div>

<div class="row g-6 g-xl-9 mb-6 mb-xl-9">
    @php
        $files = [
            ['icon' => 'pdf.svg', 'name' => 'Fabric Specs.pdf', 'desc' => '3 files by Karina Clark'],
            ['icon' => 'doc.svg', 'name' => 'Weave Guidelines.doc', 'desc' => '3 files by Melody Macy'],
            ['icon' => 'css.svg', 'name' => 'Design Styles.css', 'desc' => '4 files by Nick Stone'],
            ['icon' => 'ai.svg', 'name' => 'Pattern Design.ai', 'desc' => '2 files by Emma Smith'],
            ['icon' => 'sql.svg', 'name' => 'Fabric Database.sql', 'desc' => '5 files by John Miller'],
            ['icon' => 'xml.svg', 'name' => 'Export Data.xml', 'desc' => '1 file by Sean Bean'],
            ['icon' => 'tif.svg', 'name' => 'Texture Sample.tif', 'desc' => '7 files by Max Smith'],
            ['icon' => 'pdf.svg', 'name' => 'Quality Report.pdf', 'desc' => '2 files by Lucy Kunic'],
        ];
    @endphp

    @foreach($files as $file)
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                <a href="#" class="text-gray-800 text-hover-primary d-flex flex-column">
                    <div class="symbol symbol-60px mb-5">
                        <img src="{{ asset('modules/projectx/media/svg/files/' . $file['icon']) }}" alt="" />
                    </div>
                    <div class="fs-5 fw-bold mb-2">{{ $file['name'] }}</div>
                </a>
                <div class="fs-7 fw-semibold text-gray-500">{{ $file['desc'] }}</div>
            </div>
        </div>
    </div>
    @endforeach

    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                <a href="#" class="text-gray-800 text-hover-primary d-flex flex-column">
                    <div class="symbol symbol-60px mb-5">
                        <img src="{{ asset('modules/projectx/media/svg/files/upload.svg') }}" alt="" />
                    </div>
                    <div class="fs-5 fw-bold mb-2">{{ __('projectx::lang.upload_file') }}</div>
                </a>
                <div class="fs-7 fw-semibold text-gray-500">{{ __('projectx::lang.drag_drop_files') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
