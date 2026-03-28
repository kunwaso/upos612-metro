@extends('layouts.app')

@section('title', __('vasaccounting::lang.einvoices'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.einvoices'),
        'subtitle' => 'Provider-ready issue, sync, cancel, correct, and replace actions for posted invoice vouchers.',
    ])

    <div class="row g-5 g-xl-10">
        <div class="col-xl-7">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Issued documents</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Document no</th>
                                    <th>Provider</th>
                                    <th>Status</th>
                                    <th>Voucher</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documents as $document)
                                    <tr>
                                        <td>{{ $document->document_no ?: 'Pending' }}</td>
                                        <td>{{ $document->provider }}</td>
                                        <td><span class="badge badge-light-primary">{{ $document->status }}</span></td>
                                        <td>{{ $document->voucher_id }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.einvoices.sync', $document->id) }}" class="d-flex flex-column gap-2">
                                                @csrf
                                                <select name="provider" class="form-select form-select-sm">
                                                    @foreach ($providerOptions as $provider)
                                                        <option value="{{ $provider }}" @selected($provider === $document->provider)>{{ ucfirst($provider) }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.sync', $document->id) }}" class="btn btn-light-primary btn-sm">Sync</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.cancel', $document->id) }}" class="btn btn-light-warning btn-sm">Cancel</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.correct', $document->id) }}" class="btn btn-light-info btn-sm">Correct</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.replace', $document->id) }}" class="btn btn-light-success btn-sm">Replace</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No e-invoice records yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Eligible vouchers</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Voucher</th>
                                    <th>Type</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentVouchers as $voucher)
                                    <tr>
                                        <td>{{ $voucher->voucher_no }}</td>
                                        <td>{{ $voucher->voucher_type }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.einvoices.issue', $voucher->id) }}" class="d-flex gap-2">
                                                @csrf
                                                <select name="provider" class="form-select form-select-sm">
                                                    @foreach ($providerOptions as $provider)
                                                        <option value="{{ $provider }}" @selected($provider === $defaultProvider)>{{ ucfirst($provider) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-light-primary btn-sm">Issue</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mt-1">
        <div class="col-xl-12">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Recent provider logs</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Created at</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                    <th>Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentLogs as $log)
                                    <tr>
                                        <td>{{ $log->created_at }}</td>
                                        <td>{{ ucfirst($log->action) }}</td>
                                        <td>{{ $log->status }}</td>
                                        <td>{{ $log->einvoice_document_id }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No e-invoice provider logs yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
