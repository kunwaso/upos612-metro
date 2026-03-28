@extends('layouts.app')

@section('title', __('vasaccounting::lang.vouchers'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $voucher->voucher_no,
        'subtitle' => $voucher->description ?: 'Voucher detail and journal lines.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">Type</div><div class="text-gray-900 fw-bold fs-4">{{ $voucher->voucher_type }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">Posting date</div><div class="text-gray-900 fw-bold fs-4">{{ $voucher->posting_date }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">Status</div><div class="text-gray-900 fw-bold fs-4">{{ ucfirst($voucher->status) }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">Reference</div><div class="text-gray-900 fw-bold fs-4">{{ $voucher->reference ?: '-' }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.module_area') }}</div><div class="text-gray-900 fw-bold fs-4">{{ $voucher->module_area ?: 'accounting' }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.document_type') }}</div><div class="text-gray-900 fw-bold fs-4">{{ $voucher->document_type ?: $voucher->voucher_type }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.submitted_at') }}</div><div class="text-gray-900 fw-bold fs-4">{{ $voucher->submitted_at ?: '-' }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush"><div class="card-body"><div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.approved_at') }}</div><div class="text-gray-900 fw-bold fs-4">{{ $voucher->approved_at ?: '-' }}</div></div></div></div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title">Journal lines</div>
            <div class="card-toolbar d-flex gap-3">
                @if ($voucher->status !== 'posted')
                    <form method="POST" action="{{ route('vasaccounting.vouchers.post', $voucher->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-light-primary btn-sm">Post voucher</button>
                    </form>
                @endif
                @if ($voucher->status === 'posted')
                    <form method="POST" action="{{ route('vasaccounting.vouchers.reverse', $voucher->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-light-danger btn-sm">Reverse voucher</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>#</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($voucher->lines as $line)
                            <tr>
                                <td>{{ $line->line_no }}</td>
                                <td>{{ optional($line->account)->account_code }} - {{ optional($line->account)->account_name }}</td>
                                <td>{{ $line->description }}</td>
                                <td>{{ number_format($line->debit, 2) }}</td>
                                <td>{{ number_format($line->credit, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Totals</td>
                            <td>{{ number_format($voucher->total_debit, 2) }}</td>
                            <td>{{ number_format($voucher->total_credit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
