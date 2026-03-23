@extends('layouts.app')
@section('title', __('product.variations'))

@section('content')

{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">
                @lang('product.variations')
                <span class="text-gray-500 fw-normal fs-6 ms-2">@lang('lang_v1.manage_product_variations')</span>
            </h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">@lang('product.variations')</li>
            </ul>
        </div>
    </div>
</div>
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_variations')])
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-sm btn-primary btn-modal"
                    data-href="{{action([\App\Http\Controllers\VariationTemplateController::class, 'create'])}}" 
                    data-container=".variation_modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 5l0 14" />
                            <path d="M5 12l14 0" />
                        </svg> @lang('messages.add')
                    </a>
                </div>
            @endslot
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="variation_table">
                    <thead>
                        <tr>
                            <th>@lang('product.variations')</th>
                            <th>@lang('lang_v1.values')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent

        <div class="modal fade variation_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>
    </div>
</div>
<!-- /.content -->

@endsection
