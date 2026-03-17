@extends('projectx::layouts.main')

@section('page_title', __('projectx::lang.fabrics'))

@section('breadcrumb_items')
<li class="breadcrumb-item text-gray-500">{{ __('projectx::lang.fabrics') }}</li>
@endsection

@section('content')
<div class="card card-flush">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <h3 class="card-label fw-bold text-gray-800">
                {{ __('projectx::lang.fabrics') }}
            </h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('projectx.fabric_manager.list') }}" class="btn btn-sm btn-light-primary">
                <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('projectx::lang.back_to_fabric_manager') }}
            </a>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3" id="projectx_fabrics_table">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="min-w-220px rounded-start ps-4">{{ __('projectx::lang.fabric_name') }}</th>
                        <th class="min-w-120px">{{ __('projectx::lang.composition_summary') }}</th>
                        <th class="min-w-120px">{{ __('projectx::lang.pantone_txc') }}</th>
                        <th class="min-w-120px">{{ __('projectx::lang.weight_gsm') }}</th>
                        <th class="min-w-120px">{{ __('projectx::lang.supplier') }}</th>
                        <th class="min-w-120px">{{ __('projectx::lang.status') }}</th>
                        <th class="min-w-120px">{{ __('projectx::lang.purchase_price') }}</th>
                        <th class="min-w-120px">{{ __('projectx::lang.sale_price') }}</th>
                        <th class="min-w-100px text-end rounded-end pe-4">{{ __('projectx::lang.action') }}</th>
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
$(document).ready(function() {
    $('#projectx_fabrics_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('projectx.products') }}"
        },
        columns: [
            { data: 'fabric_name', name: 'projectx_fabrics.name' },
            { data: 'composition_summary', name: null, orderable: false, searchable: false },
            { data: 'pantone_tcx', name: 'pantone_tcx', orderable: false, searchable: true },
            { data: 'weight_gsm', name: 'projectx_fabrics.weight_gsm', orderable: true, searchable: false },
            { data: 'supplier_name', name: 'supplier.name' },
            { data: 'status', name: 'projectx_fabrics.status' },
            { data: 'purchase_price', name: 'projectx_fabrics.purchase_price' },
            { data: 'sale_price', name: 'projectx_fabrics.sale_price' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end pe-4' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            search: '',
            searchPlaceholder: '{{ __("projectx::lang.search") }}...',
        }
    });
});
</script>
@endsection
