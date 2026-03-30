@extends('layouts.app')

@section('title', __('vasaccounting::lang.integrations'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.integrations'),
        'subtitle' => 'Integration governance hub for provider queues, webhook health, replay actions, and compliance handoffs.',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($overview['metrics'] as $metric)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 fw-semibold mb-2">{{ $metric['label'] }}</div>
                        <div class="text-gray-900 fw-bold fs-2">{{ $metric['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Pending Runs</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $integrationStats['pending_runs'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Run Failures</div>
                    <div class="text-danger fw-bold fs-2">{{ $integrationStats['failed_runs'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Webhook Errors</div>
                    <div class="text-warning fw-bold fs-2">{{ $integrationStats['webhook_errors'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Open Failures</div>
                    <div class="text-danger fw-bold fs-2">{{ $integrationStats['open_failures'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Snapshot Backlog</div>
                    <div class="text-info fw-bold fs-2">{{ $integrationStats['snapshot_backlog'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Sync Candidates</div>
                    <div class="text-success fw-bold fs-2">{{ $integrationStats['sync_candidates'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($overview['provider_groups'] as $group)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-gray-900 fw-bold fs-5 mb-1">{{ $group['label'] }}</div>
                        <div class="text-muted fs-7 mb-3">Adapters: {{ $group['count'] }}</div>
                        <span class="badge badge-light-primary">Default: {{ $group['default'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Nhập sao kê ngân hàng</span>
                        <span class="text-muted fs-7">Đưa tác vụ nhập sao kê theo nhà cung cấp và tài khoản ngân hàng vào hàng đợi.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="bank_statement_import">
                        <input type="hidden" name="action" value="import_statement">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('bank_statement_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid" data-control="select2">
                                @foreach ($bankProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Tài khoản ngân hàng</label>
                            <select name="bank_account_id" class="form-select form-select-solid" data-control="select2">
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Tham chiếu</label>
                            <input type="text" name="reference_no" class="form-control form-control-solid" placeholder="STM-20260328">
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Dòng sao kê</label>
                            <textarea name="statement_lines" rows="5" class="form-control form-control-solid" placeholder="2026-03-28|Incoming transfer|1500|4500"></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">Đưa vào hàng đợi nhập</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Xuất dữ liệu thuế</span>
                        <span class="text-muted fs-7">Đưa tác vụ xuất dữ liệu tuân thủ qua các adapter đã cấu hình vào hàng đợi.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}" class="mb-8">
                        @csrf
                        <input type="hidden" name="run_type" value="tax_export">
                        <input type="hidden" name="action" value="export_tax">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('tax_export_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid">
                                @foreach ($taxProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Loại dữ liệu xuất</label>
                            <input type="text" name="export_type" class="form-control form-control-solid" value="vat_declaration">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">Đưa vào hàng đợi xuất thuế</button>
                        </div>
                    </form>

                    <div class="separator my-6"></div>

                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="payroll_bridge">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('payroll_bridge_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid">
                                @foreach ($payrollProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Chế độ</label>
                            <select name="action" class="form-select form-select-solid">
                                <option value="bridge_group">Cầu nối trích lương</option>
                                <option value="bridge_payments">Cầu nối thanh toán</option>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Nhóm bảng lương</label>
                            <select name="payroll_group_id" class="form-select form-select-solid">
                                @foreach ($payrollGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">Đưa vào hàng đợi cầu nối lương</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Đồng bộ hóa đơn điện tử</span>
                        <span class="text-muted fs-7">Kích hoạt đồng bộ trạng thái cho các chứng từ đã phát hành hoặc đang chờ đồng bộ.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="einvoice_sync">
                        <input type="hidden" name="action" value="sync_status">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('einvoice_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid">
                                @foreach ($einvoiceProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Chứng từ</label>
                            <select name="einvoice_document_id" class="form-select form-select-solid" data-control="select2">
                                @foreach ($einvoiceDocuments as $document)
                                    <option value="{{ $document->id }}">{{ $document->document_no ?: ('Chứng từ #' . $document->id) }} - {{ $vasAccountingUtil->genericStatusLabel((string) $document->status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">Đưa vào hàng đợi đồng bộ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Integration Run Queue</span>
                        <span class="text-muted fs-7">Latest run history across bank, tax, payroll, and e-invoice adapters.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Type</th>
                                    <th>Provider</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Completed</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentRuns as $run)
                                    <tr>
                                        <td>{{ $vasAccountingUtil->genericStatusLabel((string) $run->run_type) }}</td>
                                        <td>{{ $run->provider ? $vasAccountingUtil->providerLabel((string) $run->provider) : '-' }}</td>
                                        <td>{{ $run->action }}</td>
                                        <td>
                                            <span class="badge {{ $run->status === 'failed' ? 'badge-light-danger' : ($run->status === 'completed' ? 'badge-light-success' : 'badge-light-warning') }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $run->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($run->created_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ optional($run->completed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="text-muted">{{ $run->error_message ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">Chưa có tác vụ tích hợp nào được đưa vào hàng đợi.</td>
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
                        <span class="fw-bold text-gray-900">Recent Report Snapshots</span>
                        <span class="text-muted fs-7">Snapshot queue health from downstream reporting pipelines.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Snapshot</th>
                                    <th>Status</th>
                                    <th>Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSnapshots as $snapshot)
                                    <tr>
                                        <td>{{ $snapshot->snapshot_name ?: $snapshot->report_key }}</td>
                                        <td>
                                            <span class="badge {{ $snapshot->status === 'ready' ? 'badge-light-success' : 'badge-light-warning' }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $snapshot->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">Chưa có ảnh chụp báo cáo nào trong hàng đợi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Posting Failures</span>
                        <span class="text-muted fs-7">Unresolved failures with replay actions for recovery.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Source</th>
                                    <th>Failed at</th>
                                    <th>Message</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($postingFailures as $failure)
                                    <tr>
                                        <td>{{ $failure->source_type }} #{{ $failure->source_id }}</td>
                                        <td>{{ optional($failure->failed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="text-muted">{{ $failure->error_message }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('vasaccounting.integrations.failures.retry', $failure->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-light-primary btn-sm">Đưa vào hàng đợi chạy lại</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Không còn lỗi ghi sổ tồn đọng nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Webhook Log</span>
                        <span class="text-muted fs-7">Inbound provider events and processing status.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Provider</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentWebhooks as $webhook)
                                    <tr>
                                        <td>{{ $vasAccountingUtil->providerLabel((string) $webhook->provider) }}</td>
                                        <td>{{ $webhook->event_key ?: ($webhook->external_reference ?: '-') }}</td>
                                        <td>
                                            <span class="badge {{ $webhook->status === 'failed' ? 'badge-light-danger' : 'badge-light-success' }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $webhook->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($webhook->received_at)->format('Y-m-d H:i') ?: optional($webhook->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Chưa nhận webhook nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
