@extends('layouts.app')

@section('title', __('vasaccounting::lang.vouchers'))

@section('content')
    @php
        $voucherActions = '<a href="' . route('vasaccounting.vouchers.create') . '" class="btn btn-primary btn-sm">New Manual Voucher</a>';
        $voucherRows = collect($vouchers->items());
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.vouchers'),
        'subtitle' => 'System-posted source vouchers and manual journals in one statutory ledger.',
        'actions' => $voucherActions,
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Visible vouchers</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $voucherRows->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Posted</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $voucherRows->where('status', 'posted')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Pending workflow</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $voucherRows->whereIn('status', ['draft', 'pending_approval', 'approved'])->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Debit total (page)</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ number_format((float) $voucherRows->sum('total_debit'), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span>Voucher Register</span>
                <span class="text-muted fw-semibold fs-8 mt-1">Use this queue to inspect posting flow and open document details.</span>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('vasaccounting.vouchers.create') }}" class="btn btn-light-primary btn-sm">Create manual voucher</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Voucher</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Module area</th>
                            <th>Posting date</th>
                            <th class="text-end">Total debit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vouchers as $voucher)
                            <tr>
                                <td><a class="text-gray-900 fw-semibold" href="{{ route('vasaccounting.vouchers.show', $voucher->id) }}">{{ $voucher->voucher_no }}</a></td>
                                <td>{{ $voucher->voucher_type }}</td>
                                <td>{{ $voucher->source_type ?: 'manual' }}</td>
                                <td>{{ $voucher->module_area ?: 'accounting' }}</td>
                                <td>{{ $voucher->posting_date }}</td>
                                <td class="text-end">{{ number_format((float) $voucher->total_debit, 2) }}</td>
                                <td><span class="badge {{ $voucher->status === 'posted' ? 'badge-light-success' : 'badge-light-warning' }}">{{ $vasAccountingUtil->documentStatusLabel((string) $voucher->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">No vouchers found.</td>
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
