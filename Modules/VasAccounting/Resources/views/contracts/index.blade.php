@extends('layouts.app')

@section('title', __('vasaccounting::lang.contracts'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.contracts'),
        'subtitle' => 'Track contract lifecycle, milestone billing, and revenue recognition in one planning workspace.',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Contracts</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['contract_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">Contracts in current scope</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Active Contracts</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['active_contracts']) }}</div>
                    <div class="text-muted fs-8 mt-1">Statuses currently marked active</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Due Milestones</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['due_milestones']) }}</div>
                    <div class="text-muted fs-8 mt-1">Planned and ready-to-bill milestones</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Recognized Revenue</div>
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
                        <span class="text-gray-900 fw-bold">Register Contract</span>
                        <span class="text-muted fs-7">Create base contract metadata and financial values.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.contracts.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">Contract no</label>
                                <input type="text" name="contract_no" class="form-control" placeholder="HD-001" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Contract name</label>
                                <input type="text" name="name" class="form-control" placeholder="Warehouse fit-out agreement" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Counterparty</label>
                                <select name="contact_id" class="form-select" data-control="select2">
                                    <option value="">Select contact</option>
                                    @foreach ($contactOptions as $contactId => $contactLabel)
                                        <option value="{{ $contactId }}">{{ $contactLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">Select project</option>
                                    @foreach ($projectOptions as $projectId => $projectLabel)
                                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost center</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">Select cost center</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                        <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch</label>
                                <select name="business_location_id" class="form-select" data-control="select2">
                                    <option value="">Select branch</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Signed at</label>
                                <input type="date" name="signed_at" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contract value</label>
                                <input type="number" step="0.0001" min="0" name="contract_value" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Advance amount</label>
                                <input type="number" step="0.0001" min="0" name="advance_amount" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Retention amount</label>
                                <input type="number" step="0.0001" min="0" name="retention_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">Save contract</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header align-items-center py-5">
                    <div class="card-title d-flex flex-column">
                        <span class="text-gray-900 fw-bold">Add Contract Milestone</span>
                        <span class="text-muted fs-7">Schedule recognition milestones against active contracts.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.contracts.milestones.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">Contract</label>
                                <select name="contract_id" class="form-select" data-control="select2" required>
                                    <option value="">Select contract</option>
                                    @foreach ($contractOptions as $contractId => $contractLabel)
                                        <option value="{{ $contractId }}">{{ $contractLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Milestone no</label>
                                <input type="text" name="milestone_no" class="form-control" placeholder="MS-01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Milestone name</label>
                                <input type="text" name="name" class="form-control" placeholder="Design approval" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Milestone date</label>
                                <input type="date" name="milestone_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Billing date</label>
                                <input type="date" name="billing_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Revenue amount</label>
                                <input type="number" step="0.0001" min="0" name="revenue_amount" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Advance tracked</label>
                                <input type="number" step="0.0001" min="0" name="advance_amount" class="form-control" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Retention tracked</label>
                                <input type="number" step="0.0001" min="0" name="retention_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">Save milestone</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header align-items-center py-5">
            <div class="card-title d-flex flex-column">
                <span class="text-gray-900 fw-bold">Contract Register</span>
                <span class="text-muted fs-7">Commercial obligations and recognition progress by contract.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Contract</th>
                            <th>Counterparty / Project</th>
                            <th>Branch</th>
                            <th>Value</th>
                            <th>Recognized</th>
                            <th>Remaining</th>
                            <th>Status</th>
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
                                    <div class="text-muted fs-8">{{ optional($contract->project)->name ?: 'No project linked' }}</div>
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
                                <td colspan="7" class="text-muted">No contracts are available for this scope.</td>
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
                <span class="text-gray-900 fw-bold">Milestones and Recognition</span>
                <span class="text-muted fs-7">Post eligible milestones to generate contract accounting entries.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Milestone</th>
                            <th>Contract</th>
                            <th>Dates</th>
                            <th>Revenue</th>
                            <th>Retention</th>
                            <th>Status</th>
                            <th>Post</th>
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
                                    <div class="text-muted fs-8">Bill: {{ optional($milestone->billing_date)->format('Y-m-d') ?: '-' }}</div>
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
                                            <button type="submit" class="btn btn-light-primary btn-sm">Post milestone</button>
                                        </form>
                                    @else
                                        <div class="text-muted fs-8">Voucher #{{ $milestone->posted_voucher_id ?: '-' }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">No contract milestones are available for this scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
