@extends('layouts.app')

@section('title', __('vasaccounting::lang.vouchers'))

@section('content')
    @php
        $voucherActions = '<a href="' . route('vasaccounting.vouchers.create') . '" class="btn btn-primary btn-sm">' . __('vasaccounting::lang.views.vouchers.index.new_manual_voucher') . '</a>';
        $voucherRows = collect($vouchers->items());
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.vouchers'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
        'actions' => $voucherActions,
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.vouchers.index.cards.visible_vouchers') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $voucherRows->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.vouchers.index.cards.posted') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $voucherRows->where('status', 'posted')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.vouchers.index.cards.pending_workflow') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $voucherRows->whereIn('status', ['draft', 'pending_approval', 'approved'])->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.vouchers.index.cards.debit_total') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ number_format((float) $voucherRows->sum('total_debit'), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span>{{ __('vasaccounting::lang.views.vouchers.index.register.title') }}</span>
                <span class="text-muted fw-semibold fs-8 mt-1">{{ __('vasaccounting::lang.views.vouchers.index.register.subtitle') }}</span>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('vasaccounting.vouchers.create') }}" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.vouchers.index.register.create_manual_voucher') }}</a>
            </div>
        </div>
        <div class="card-body">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-vouchers-register-search',
                'actions' => [
                    [
                        'label' => $vasAccountingUtil->actionLabel('open_reports'),
                        'url' => route('vasaccounting.reports.index'),
                        'style' => 'light-primary',
                        'method' => 'GET',
                    ],
                ],
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="vas-vouchers-register-table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.vouchers.index.table.voucher') }}</th>
                            <th>{{ __('vasaccounting::lang.views.vouchers.index.table.type') }}</th>
                            <th>{{ __('vasaccounting::lang.views.vouchers.index.table.source') }}</th>
                            <th>{{ __('vasaccounting::lang.views.vouchers.index.table.module_area') }}</th>
                            <th>{{ __('vasaccounting::lang.views.vouchers.index.table.posting_date') }}</th>
                            <th class="text-end">{{ __('vasaccounting::lang.views.vouchers.index.table.total_debit') }}</th>
                            <th>{{ __('vasaccounting::lang.views.vouchers.index.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vouchers as $voucher)
                            @php
                                $sourceType = trim((string) $voucher->source_type);
                                $sourceLabel = $sourceType !== '' ? preg_replace('/(?:_reversal)+$/', '', $sourceType) : __('vasaccounting::lang.views.vouchers.index.source_manual');
                                $isReversalSource = (bool) $voucher->is_reversal || ($sourceType !== '' && str_ends_with(strtolower($sourceType), '_reversal'));
                            @endphp
                            <tr>
                                <td><a class="text-gray-900 fw-semibold" href="{{ route('vasaccounting.vouchers.show', $voucher->id) }}">{{ $voucher->voucher_no }}</a></td>
                                <td>{{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }}</td>
                                <td>
                                    <span>{{ $sourceLabel }}</span>
                                    @if ($isReversalSource)
                                        <span class="badge badge-light-warning ms-2">{{ __('vasaccounting::lang.actions.reverse') }}</span>
                                    @endif
                                </td>
                                <td>{{ $vasAccountingUtil->moduleAreaLabel((string) ($voucher->module_area ?: 'accounting')) }}</td>
                                <td>{{ $voucher->posting_date }}</td>
                                <td class="text-end">{{ number_format((float) $voucher->total_debit, 2) }}</td>
                                <td><span class="badge {{ $voucher->status === 'posted' ? 'badge-light-success' : 'badge-light-warning' }}">{{ $vasAccountingUtil->documentStatusLabel((string) $voucher->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.vouchers.index.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $vouchers->links() }}
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const vouchersTable = window.VasWorkspace?.initLocalDataTable('#vas-vouchers-register-table', {
                order: [[4, 'desc']],
                pageLength: 20,
                paging: false,
                info: false,
                dom: "<'table-responsive'tr>"
            });

            if (vouchersTable) {
                $('#vas-vouchers-register-search').on('keyup', function () {
                    vouchersTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
