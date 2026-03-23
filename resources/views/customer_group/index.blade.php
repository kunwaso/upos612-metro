@extends('layouts.app')
@section('title', __('lang_v1.customer_groups'))

@section('content')

{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">@lang('lang_v1.customer_groups')</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">@lang('lang_v1.customer_groups')</li>
            </ul>
        </div>
    </div>
</div>
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_your_customer_groups')])
            @can('customer.create')
                @slot('tool')
                    <div class="box-tools">
                        <a class="btn btn-sm btn-primary btn-modal"
                            data-href="{{ action([\App\Http\Controllers\CustomerGroupController::class, 'create']) }}"
                            data-container=".customer_groups_modal">
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
            @endcan
            @can('customer.view')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="customer_groups_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.customer_group_name')</th>
                                <th>@lang('lang_v1.calculation_percentage')</th>
                                <th>@lang('lang_v1.selling_price_group')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcan
        @endcomponent

        <div class="modal fade customer_groups_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>
    </div>
</div>
<!-- /.content -->
@stop
@section('javascript')

    <script type="text/javascript">
        $(document).on('change', '#price_calculation_type', function() {
            var price_calculation_type = $(this).val();

            if (price_calculation_type == 'percentage') {
                $('.percentage-field').removeClass('hide');
                $('.selling_price_group-field').addClass('hide');
            } else {
                $('.percentage-field').addClass('hide');
                $('.selling_price_group-field').removeClass('hide');
            }
        })
    </script>
@endsection
