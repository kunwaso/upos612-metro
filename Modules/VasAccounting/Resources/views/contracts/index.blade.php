@extends('layouts.app')

@section('title', __('vasaccounting::lang.contracts'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.contracts'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.contracts.cards.contracts') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['contract_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.contracts.cards.contracts_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.contracts.cards.active_contracts') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['active_contracts']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.contracts.cards.active_contracts_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.contracts.cards.due_milestones') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['due_milestones']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.contracts.cards.due_milestones_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.contracts.cards.recognized_revenue') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((float) $summary['recognized_revenue'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header align-items-center py-5">
                    <div class="card-title d-flex flex-column">
                        <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.contracts.register.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.contracts.register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.contracts.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.contract_no') }}</label>
                                <input type="text" name="contract_no" class="form-control" placeholder="HD-001" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.contract_name') }}</label>
                                <input type="text" name="name" class="form-control" placeholder="{{ __('vasaccounting::lang.views.contracts.register.placeholders.contract_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.counterparty') }}</label>
                                <select name="contact_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.contracts.register.select_contact') }}</option>
                                    @foreach ($contactOptions as $contactId => $contactLabel)
                                        <option value="{{ $contactId }}">{{ $contactLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.project') }}</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_project') }}</option>
                                    @foreach ($projectOptions as $projectId => $projectLabel)
                                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.cost_center') }}</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_cost_center') }}</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                        <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                                <select name="business_location_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.signed_at') }}</label>
                                <input type="date" name="signed_at" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.start_date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.end_date') }}</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.contract_value') }}</label>
                                <input type="number" step="0.0001" min="0" name="contract_value" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.advance_amount') }}</label>
                                <input type="number" step="0.0001" min="0" name="advance_amount" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.register.fields.retention_amount') }}</label>
                                <input type="number" step="0.0001" min="0" name="retention_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.contracts.register.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header align-items-center py-5">
                    <div class="card-title d-flex flex-column">
                        <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.contracts.milestones.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.contracts.milestones.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.contracts.milestones.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.contract') }}</label>
                                <select name="contract_id" class="form-select" data-control="select2" required>
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_contract') }}</option>
                                    @foreach ($contractOptions as $contractId => $contractLabel)
                                        <option value="{{ $contractId }}">{{ $contractLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.milestone_no') }}</label>
                                <input type="text" name="milestone_no" class="form-control" placeholder="MS-01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.milestone_name') }}</label>
                                <input type="text" name="name" class="form-control" placeholder="{{ __('vasaccounting::lang.views.contracts.milestones.placeholders.milestone_name') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.milestone_date') }}</label>
                                <input type="date" name="milestone_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.billing_date') }}</label>
                                <input type="date" name="billing_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.revenue_amount') }}</label>
                                <input type="number" step="0.0001" min="0" name="revenue_amount" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.advance_tracked') }}</label>
                                <input type="number" step="0.0001" min="0" name="advance_amount" class="form-control" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.contracts.milestones.fields.retention_tracked') }}</label>
                                <input type="number" step="0.0001" min="0" name="retention_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.contracts.milestones.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header align-items-center py-5">
            <div class="card-title d-flex flex-column">
                <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.contracts.contract_register.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.contracts.contract_register.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.contracts.contract_register.table.contract') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.contract_register.table.counterparty_project') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.branch') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.contract_register.table.value') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.contract_register.table.recognized') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.contract_register.table.remaining') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contractRows as $row)
                            @php($contract = $row['contract'])
                            <tr>
                                <td>
                                    <div class="text-gray-900 fw-semibold">{{ $contract->contract_no }}</div>
                                    <div class="text-muted fs-8">{{ $contract->name }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($contract->contact)->name ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ optional($contract->project)->name ?: __('vasaccounting::lang.views.contracts.contract_register.no_project') }}</div>
                                </td>
                                <td>{{ optional($contract->businessLocation)->name ?: '-' }}</td>
                                <td>{{ number_format((float) $contract->contract_value, 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['recognized_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['remaining_value'], 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="badge {{ $contract->status === 'active' ? 'badge-light-success' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $contract->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.contracts.contract_register.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header align-items-center py-5">
            <div class="card-title d-flex flex-column">
                <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.contracts.recognition.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.contracts.recognition.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.contracts.recognition.table.milestone') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.recognition.table.contract') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.recognition.table.dates') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.recognition.table.revenue') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.recognition.table.retention') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.contracts.recognition.table.post') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($milestoneRows as $milestone)
                            <tr>
                                <td>
                                    <div class="text-gray-900 fw-semibold">{{ $milestone->milestone_no }}</div>
                                    <div class="text-muted fs-8">{{ $milestone->name }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($milestone->contract)->contract_no ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ optional(optional($milestone->contract)->contact)->name ?: '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($milestone->milestone_date)->format('Y-m-d') ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.contracts.recognition.bill_label', ['date' => optional($milestone->billing_date)->format('Y-m-d') ?: '-']) }}</div>
                                </td>
                                <td>{{ number_format((float) $milestone->revenue_amount, 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $milestone->retention_amount, 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="badge {{ $milestone->status === 'posted' ? 'badge-light-success' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $milestone->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($milestone->status !== 'posted')
                                        <form method="POST" action="{{ route('vasaccounting.contracts.milestones.post', $milestone->id) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            <input type="date" name="posted_at" class="form-control form-control-sm" value="{{ optional($milestone->billing_date)->format('Y-m-d') ?: now()->format('Y-m-d') }}">
                                            <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.contracts.recognition.actions.post_milestone') }}</button>
                                        </form>
                                    @else
                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.contracts.recognition.voucher_label', ['id' => $milestone->posted_voucher_id ?: '-']) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.contracts.recognition.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
