@extends('layouts.app')

@section('title', __('vasaccounting::lang.vouchers'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.vouchers'),
        'subtitle' => 'System-posted source vouchers and manual journals in one statutory ledger.',
        'actions' => '<a href="' . route('vasaccounting.vouchers.create') . '" class="btn btn-primary btn-sm">New Manual Voucher</a>',
    ])

    <div class="card card-flush">
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
                            <th>Total debit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vouchers as $voucher)
                            <tr>
                                <td><a class="text-gray-900 fw-semibold" href="{{ route('vasaccounting.vouchers.show', $voucher->id) }}">{{ $voucher->voucher_no }}</a></td>
                                <td>{{ $voucher->voucher_type }}</td>
                                <td>{{ $voucher->source_type ?: 'manual' }}</td>
                                <td>{{ $voucher->module_area ?: 'accounting' }}</td>
                                <td>{{ $voucher->posting_date }}</td>
                                <td>{{ number_format($voucher->total_debit, 2) }}</td>
                                <td><span class="badge {{ $voucher->status === 'posted' ? 'badge-light-success' : 'badge-light-warning' }}">{{ ucfirst($voucher->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $vouchers->links() }}
            </div>
        </div>
    </div>
@endsection
