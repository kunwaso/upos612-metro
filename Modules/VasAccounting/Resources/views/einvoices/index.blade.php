@extends('layouts.app')

@section('title', __('vasaccounting::lang.einvoices'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.einvoices'),
        'subtitle' => 'Provider issue, sync, and correction workflows for posted invoice vouchers.',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Documents</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">Issued and tracked e-invoices</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Ready to Issue</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['ready_to_issue']) }}</div>
                    <div class="text-muted fs-8 mt-1">Eligible posted vouchers</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Failed / Rejected</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['failed_or_rejected']) }}</div>
                    <div class="text-muted fs-8 mt-1">Need provider follow-up</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Synced Today</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['synced_today']) }}</div>
                    <div class="text-muted fs-8 mt-1">Recent provider updates</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Issued Documents</span>
                        <span class="text-muted fs-7">Run sync, cancel, correction, and replacement actions by provider.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
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
                                        <td class="fw-semibold text-gray-900">{{ $document->document_no ?: 'Chờ phát hành' }}</td>
                                        <td>{{ $vasAccountingUtil->providerLabel((string) $document->provider, 'einvoice_adapters') }}</td>
                                        <td>
                                            <span class="badge {{ in_array((string) $document->status, ['failed', 'rejected'], true) ? 'badge-light-danger' : (in_array((string) $document->status, ['issued', 'synced'], true) ? 'badge-light-success' : 'badge-light-primary') }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $document->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $document->voucher_id }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.einvoices.sync', $document->id) }}" class="d-flex flex-column gap-2">
                                                @csrf
                                                <select name="provider" class="form-select form-select-sm">
                                                    @foreach ($providerOptions as $providerKey => $providerLabel)
                                                        <option value="{{ $providerKey }}" @selected($providerKey === $document->provider)>{{ $providerLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Ghi chú thao tác (tùy chọn)">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.sync', $document->id) }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('sync') }}</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.cancel', $document->id) }}" class="btn btn-light-warning btn-sm">{{ $vasAccountingUtil->actionLabel('cancel') }}</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.correct', $document->id) }}" class="btn btn-light-info btn-sm">{{ $vasAccountingUtil->actionLabel('correct') }}</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.replace', $document->id) }}" class="btn btn-light-success btn-sm">{{ $vasAccountingUtil->actionLabel('replace') }}</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">Chưa có bản ghi hóa đơn điện tử.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Hàng đợi phát hành</span>
                        <span class="text-muted fs-7">Chọn nhà cung cấp và phát hành từ các chứng từ đủ điều kiện đã ghi sổ.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Chứng từ</th>
                                    <th>Loại</th>
                                    <th>Phát hành</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentVouchers as $voucher)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $voucher->voucher_no }}</td>
                                        <td>{{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.einvoices.issue', $voucher->id) }}" class="d-flex gap-2">
                                                @csrf
                                                <select name="provider" class="form-select form-select-sm">
                                                    @foreach ($providerOptions as $providerKey => $providerLabel)
                                                        <option value="{{ $providerKey }}" @selected($providerKey === $defaultProvider)>{{ $providerLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('issue_einvoice') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">Không có chứng từ đủ điều kiện phát hành.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">Nhật ký nhà cung cấp gần đây</span>
                <span class="text-muted fs-7">Theo dõi lịch sử đồng bộ và phản hồi thao tác.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Thời điểm tạo</th>
                            <th>Thao tác</th>
                            <th>Trạng thái</th>
                            <th>Chứng từ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLogs as $log)
                            <tr>
                                <td>{{ $log->created_at }}</td>
                                <td>{{ $vasAccountingUtil->actionLabel((string) $log->action) }}</td>
                                <td>
                                    <span class="badge {{ in_array((string) $log->status, ['failed', 'rejected'], true) ? 'badge-light-danger' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $log->status) }}
                                    </span>
                                </td>
                                <td>{{ $log->einvoice_document_id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Chưa có nhật ký nhà cung cấp hóa đơn điện tử.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
