@extends('layouts.app')

@section('title', 'Payment Documents')

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => 'Payment Documents',
        'subtitle' => 'Native cash and bank receipts/payments with approval and posting lifecycle.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Native payment register</div>
                    <div class="card-toolbar">
                        <a href="{{ route('vasaccounting.payment_documents.create') }}" class="btn btn-primary btn-sm">New payment</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Voucher</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documents as $document)
                                    <tr>
                                        <td><a href="{{ route('vasaccounting.payment_documents.show', $document->id) }}">{{ $document->voucher_no }}</a></td>
                                        <td>{{ str_replace('_', ' ', $document->voucher_type) }}</td>
                                        <td>{{ $document->reference ?: '-' }}</td>
                                        <td>{{ str_replace('_', ' ', $document->status) }}</td>
                                        <td>{{ $document->posting_date }}</td>
                                        <td class="text-end">{{ number_format((float) max($document->total_debit, $document->total_credit), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">No native payment documents yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">
                        {{ $documents->links() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">Open payable items</div>
                </div>
                <div class="card-body">
                    @forelse ($payableOpenItems as $item)
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold">{{ $item->voucher_no }}</div>
                                <div class="text-muted fs-7">{{ $item->contact_name }}</div>
                            </div>
                            <div class="text-end">{{ number_format((float) $item->outstanding_amount, 2) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No payable items are currently open.</div>
                    @endforelse
                </div>
            </div>
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Open receivable items</div>
                </div>
                <div class="card-body">
                    @forelse ($receivableOpenItems as $item)
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold">{{ $item->voucher_no }}</div>
                                <div class="text-muted fs-7">{{ $item->contact_name }}</div>
                            </div>
                            <div class="text-end">{{ number_format((float) $item->outstanding_amount, 2) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No receivable items are currently open.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
