@extends('layouts.app')

@section('title', __('lang_v1.unified_quotes'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('lang_v1.unified_quotes') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('lang_v1.unified_quotes_description') }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($canSales && auth()->user()->can('direct_sell.access'))
            <a href="{{ action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'quotation']) }}" class="btn btn-light-primary btn-sm">
                <i class="ki-duotone ki-document fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('lang_v1.add_quotation') }}
            </a>
        @endif
        @can('product_quote.create')
            <a href="{{ route('product.quotes.create') }}" class="btn btn-primary btn-sm">
                <i class="ki-duotone ki-plus fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('product.create_quote') }}
            </a>
        @endcan
    </div>
</div>

<div class="card card-flush mb-5">
    <div class="card-header">
        <h3 class="card-title fw-bold text-gray-800">{{ __('report.filters') }}</h3>
    </div>
    <div class="card-body py-4">
        <div class="row g-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="hub_filter_location_id">{{ __('purchase.business_location') }}</label>
                {!! Form::select('hub_filter_location_id', $business_locations, null, ['class' => 'form-select form-select-solid', 'id' => 'hub_filter_location_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
            <div class="col-md-3">
                <label class="form-label" for="hub_filter_customer_id">{{ __('contact.customer') }}</label>
                {!! Form::select('hub_filter_customer_id', $customers, null, ['class' => 'form-select form-select-solid select2', 'id' => 'hub_filter_customer_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
            <div class="col-md-3">
                <label class="form-label" for="hub_filter_date_range">{{ __('report.date_range') }}</label>
                {!! Form::text('hub_filter_date_range', null, ['class' => 'form-control form-control-solid', 'id' => 'hub_filter_date_range', 'placeholder' => __('lang_v1.select_a_date_range'), 'readonly']); !!}
            </div>
            <div class="col-md-3">
                <label class="form-label" for="quote_kind_filter">{{ __('lang_v1.filter_quote_type') }}</label>
                <select class="form-select form-select-solid" id="quote_kind_filter" name="quote_kind_filter">
                    <option value="all">{{ __('lang_v1.filter_quote_type_all') }}</option>
                    @if($canSales)
                        <option value="sales_quotation">{{ __('lang_v1.quote_kind_sales_quotation') }}</option>
                    @endif
                    @if($canProduct)
                        <option value="product_quote">{{ __('lang_v1.quote_kind_product_quote') }}</option>
                    @endif
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card card-flush">
    <div class="card-header pt-7">
        <div class="card-title">
            <h3 class="fw-bold text-gray-900">{{ __('lang_v1.unified_quotes') }}</h3>
        </div>
    </div>
    <div class="card-body pt-5">
        <div class="table-responsive">
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="unified_quotes_table">
                <thead>
                    <tr class="fw-bold text-muted text-uppercase fs-7">
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('purchase.ref_no') }}</th>
                        <th>{{ __('lang_v1.filter_quote_type') }}</th>
                        <th>{{ __('sale.customer_name') }}</th>
                        <th>{{ __('sale.location') }}</th>
                        <th>{{ __('sale.total') }}</th>
                        <th>{{ __('product.status') }}</th>
                        <th class="text-end">{{ __('product.action') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
(function () {
    const table = $('#unified_quotes_table');
    if (!table.length) return;

    const hubTable = table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('quotes.hub.data') }}',
            data: function (d) {
                const range = $('#hub_filter_date_range').data('daterangepicker');
                if (range && $('#hub_filter_date_range').val()) {
                    d.start_date = range.startDate.format('YYYY-MM-DD');
                    d.end_date = range.endDate.format('YYYY-MM-DD');
                }
                d.location_id = $('#hub_filter_location_id').val() || '';
                d.customer_id = $('#hub_filter_customer_id').val() || '';
                d.quote_kind_filter = $('#quote_kind_filter').val() || 'all';
            }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'quote_sort_at', name: 'quote_sort_at', searchable: false },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'quote_type', name: 'quote_type', orderable: false, searchable: false },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'location_name', name: 'location_name', defaultContent: '-', searchable: true },
            { data: 'amount', name: 'amount', searchable: false },
            { data: 'status_label', name: 'status_label', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
        ]
    });

    if (typeof dateRangeSettings !== 'undefined') {
        $('#hub_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#hub_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                hubTable.ajax.reload();
            }
        );
        $('#hub_filter_date_range').on('cancel.daterangepicker', function () {
            $(this).val('');
            hubTable.ajax.reload();
        });
    }

    $('#hub_filter_location_id, #quote_kind_filter').on('change', function () {
        hubTable.ajax.reload();
    });
    $('#hub_filter_customer_id').on('change', function () {
        hubTable.ajax.reload();
    });
})();
</script>
@endsection
