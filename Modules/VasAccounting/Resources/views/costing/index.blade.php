@extends('layouts.app')

@section('title', __('vasaccounting::lang.costing'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.costing'),
        'subtitle' => 'Maintain departments, cost centers, and projects for dimension-based planning and analysis.',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Departments</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['departments']) }}</div>
                    <div class="text-muted fs-8 mt-1">Active branch-aware department structures.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Cost Centers</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['cost_centers']) }}</div>
                    <div class="text-muted fs-8 mt-1">Allocation points used in journals and projects.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Projects</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['projects']) }}</div>
                    <div class="text-muted fs-8 mt-1">Project dimensions tied to costing analysis.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Dimension Activity</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['dimensioned_entries']) }}</div>
                    <div class="text-muted fs-8 mt-1">Dimensioned journal-entry activity in this scope.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Create Department</span>
                        <span class="text-muted fs-7">Set branch ownership for the department dimension.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.costing.departments.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="DPT-ADM" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Administration" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Branch</label>
                            <select name="business_location_id" class="form-select" data-control="select2">
                                <option value="">Select branch</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">Save department</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Create Cost Center</span>
                        <span class="text-muted fs-7">Assign cost centers to departments for granular postings.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.costing.cost_centers.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="CC-001" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Warehouse operations" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" data-control="select2">
                                <option value="">Select department</option>
                                @foreach ($departmentOptions as $departmentId => $departmentLabel)
                                    <option value="{{ $departmentId }}">{{ $departmentLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">Save cost center</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Create Project</span>
                        <span class="text-muted fs-7">Attach customer, cost center, and planning dates.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.costing.projects.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Project code</label>
                            <input type="text" name="project_code" class="form-control" placeholder="PJ-001" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Project name</label>
                            <input type="text" name="name" class="form-control" placeholder="Factory expansion" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Customer / vendor</label>
                            <select name="contact_id" class="form-select" data-control="select2">
                                <option value="">Select contact</option>
                                @foreach ($contactOptions as $contactId => $contactLabel)
                                    <option value="{{ $contactId }}">{{ $contactLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Cost center</label>
                            <select name="cost_center_id" class="form-select" data-control="select2">
                                <option value="">Select cost center</option>
                                @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                    <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Budget</label>
                                <input type="number" step="0.0001" min="0" name="budget_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">Save project</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Department Register</span>
                        <span class="text-muted fs-7">Department-level activity and cost-center count.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Department</th>
                                    <th>Cost Centers</th>
                                    <th>Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($departmentRows as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-gray-900">{{ data_get($row, 'department.code') }}</div>
                                            <div class="text-muted fs-8">{{ data_get($row, 'department.name') }}</div>
                                        </td>
                                        <td>{{ number_format((int) $row['cost_center_count']) }}</td>
                                        <td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">No departments found in this scope.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Cost Center Register</span>
                        <span class="text-muted fs-7">Project count and activity by cost center.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Cost Center</th>
                                    <th>Projects</th>
                                    <th>Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($costCenterRows as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-gray-900">{{ data_get($row, 'cost_center.code') }}</div>
                                            <div class="text-muted fs-8">{{ data_get($row, 'cost_center.name') }}</div>
                                        </td>
                                        <td>{{ number_format((int) $row['project_count']) }}</td>
                                        <td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">No cost centers found in this scope.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Project Register</span>
                        <span class="text-muted fs-7">Budget versus actual activity by project.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Project</th>
                                    <th>Budget</th>
                                    <th>Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($projectRows as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-gray-900">{{ data_get($row, 'project.project_code') }}</div>
                                            <div class="text-muted fs-8">{{ data_get($row, 'project.name') }}</div>
                                        </td>
                                        <td>{{ number_format((float) data_get($row, 'project.budget_amount', 0), 2) }} {{ $currency }}</td>
                                        <td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">No projects found in this scope.</td>
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
