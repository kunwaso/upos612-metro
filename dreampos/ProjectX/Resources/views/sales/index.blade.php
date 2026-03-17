@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_quotes'))

@section('content')






<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.fabric_quotes') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.fabric_quotes_description') }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('projectx.sales.orders.index') }}" class="btn btn-light-primary btn-sm">
            <i class="ki-duotone ki-graph-up fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('projectx::lang.sales_orders') }}
        </a>
        @can('projectx.quote.create')
            <a href="{{ route('projectx.quotes.create') }}" class="btn btn-primary btn-sm">
                <i class="ki-duotone ki-plus fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('projectx::lang.create_quote') }}
            </a>
        @endcan
    </div>
</div>

<div class="card card-flush">
    <div class="card-header pt-7">
        <div class="card-title">
            <h3 class="fw-bold text-gray-900">{{ __('projectx::lang.quotes') }}</h3>
        </div>
    </div>
    <div class="card-body pt-5">
        <div class="table-responsive">
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="projectx_quotes_table">
                <thead>
                    <tr class="fw-bold text-muted text-uppercase fs-7">
                        <th>{{ __('projectx::lang.date') }}</th>
                        <th>{{ __('projectx::lang.quote_no') }}</th>
                        <th>{{ __('projectx::lang.customer') }}</th>
                        <th>{{ __('projectx::lang.quote_type') }}</th>
                        <th>{{ __('projectx::lang.lines') }}</th>
                        <th>{{ __('projectx::lang.location') }}</th>
                        <th>{{ __('projectx::lang.grand_total') }}</th>
                        <th>{{ __('projectx::lang.currency_label') }}</th>
                        <th>{{ __('projectx::lang.incoterm') }}</th>
                        <th>{{ __('projectx::lang.status') }}</th>
                        <th>{{ __('projectx::lang.sent_at') }}</th>
                        <th>{{ __('projectx::lang.expires_at') }}</th>
                        <th class="text-end">{{ __('projectx::lang.action') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
    (function () {
        const table = $('#projectx_quotes_table');
        if (!table.length) {
            return;
        }

        table.DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('projectx.sales') }}',
            order: [[0, 'desc']],
            columns: [
                {data: 'created_at', name: 'projectx_quotes.created_at'},
                {data: 'quote_number', name: 'projectx_quotes.quote_number'},
                {data: 'customer_name', name: 'customer_name', defaultContent: '-'},
                {data: 'quote_type', name: 'quote_type', orderable: false, searchable: false},
                {data: 'line_count', name: 'projectx_quotes.line_count'},
                {data: 'location_name', name: 'location_name', defaultContent: '-'},
                {data: 'grand_total', name: 'projectx_quotes.grand_total'},
                {data: 'currency', name: 'projectx_quotes.currency', defaultContent: '-'},
                {data: 'incoterm', name: 'projectx_quotes.incoterm', defaultContent: '-'},
                {data: 'quote_state', name: 'quote_state', orderable: false, searchable: false},
                {data: 'sent_at', name: 'projectx_quotes.sent_at'},
                {data: 'expires_at', name: 'projectx_quotes.expires_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
            ]
        });
    })();
</script>
@endsection
