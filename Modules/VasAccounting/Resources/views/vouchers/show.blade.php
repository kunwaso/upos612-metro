@extends('layouts.app')

@section('title', __('vasaccounting::lang.vouchers'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $voucher->voucher_no,
        'subtitle' => $voucher->description ?: 'Voucher detail and journal lines.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">Type</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->voucher_type }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">Posting date</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->posting_date }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">Status</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $vasAccountingUtil->documentStatusLabel((string) $voucher->status) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">Reference</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->reference ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.module_area') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->module_area ?: 'accounting' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.document_type') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->document_type ?: $voucher->voucher_type }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.submitted_at') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->submitted_at ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.approved_at') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->approved_at ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Journal lines</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>#</th>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($voucher->lines as $line)
                                    <tr>
                                        <td>{{ $line->line_no }}</td>
                                        <td>{{ optional($line->account)->account_code }} - {{ optional($line->account)->account_name }}</td>
                                        <td>{{ $line->description }}</td>
                                        <td class="text-end">{{ number_format((float) $line->debit, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $line->credit, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Totals</td>
                                    <td class="text-end">{{ number_format((float) $voucher->total_debit, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $voucher->total_credit, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">Actions</div>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    @if ($voucher->status !== 'posted')
                        <form method="POST" action="{{ route('vasaccounting.vouchers.post', $voucher->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-light-primary btn-sm w-100">Post voucher</button>
                        </form>
                    @endif

                    @if ($voucher->status === 'posted')
                        <form method="POST" action="{{ route('vasaccounting.vouchers.reverse', $voucher->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-light-danger btn-sm w-100">Reverse voucher</button>
                        </form>
                    @endif

                    <a href="{{ route('vasaccounting.vouchers.create') }}" class="btn btn-light btn-sm">Create new voucher</a>
                    <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light btn-sm">Back to register</a>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Audit Snapshot</div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted fs-7">Debit total</span>
                        <span class="text-gray-900 fw-semibold">{{ number_format((float) $voucher->total_debit, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted fs-7">Credit total</span>
                        <span class="text-gray-900 fw-semibold">{{ number_format((float) $voucher->total_credit, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fs-7">Balanced</span>
                        <span class="badge {{ (float) $voucher->total_debit === (float) $voucher->total_credit ? 'badge-light-success' : 'badge-light-danger' }}">
                            {{ (float) $voucher->total_debit === (float) $voucher->total_credit ? 'Yes' : 'No' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
