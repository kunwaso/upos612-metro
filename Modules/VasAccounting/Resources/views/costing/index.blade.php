@extends('layouts.app')

@section('title', __('vasaccounting::lang.costing'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.costing'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.costing.cards.departments') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['departments']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.costing.cards.departments_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.costing.cards.cost_centers') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['cost_centers']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.costing.cards.cost_centers_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.costing.cards.projects') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['projects']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.costing.cards.projects_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.costing.cards.dimension_activity') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['dimensioned_entries']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.costing.cards.dimension_activity_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.costing.department_form.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.costing.department_form.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.costing.departments.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.costing.department_form.fields.code') }}</label>
                            <input type="text" name="code" class="form-control" placeholder="DPT-ADM" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.costing.department_form.fields.name') }}</label>
                            <input type="text" name="name" class="form-control" placeholder="{{ __('vasaccounting::lang.views.costing.department_form.placeholders.name') }}" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                            <select name="business_location_id" class="form-select" data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.costing.department_form.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.costing.cost_center_form.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.costing.cost_center_form.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.costing.cost_centers.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.costing.cost_center_form.fields.code') }}</label>
                            <input type="text" name="code" class="form-control" placeholder="CC-001" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.costing.cost_center_form.fields.name') }}</label>
                            <input type="text" name="name" class="form-control" placeholder="{{ __('vasaccounting::lang.views.costing.cost_center_form.placeholder') }}" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.department') }}</label>
                            <select name="department_id" class="form-select" data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_department') }}</option>
                                @foreach ($departmentOptions as $departmentId => $departmentLabel)
                                    <option value="{{ $departmentId }}">{{ $departmentLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.costing.cost_center_form.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.costing.project_form.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.costing.project_form.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.costing.projects.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.costing.project_form.project_code') }}</label>
                            <input type="text" name="project_code" class="form-control" placeholder="PJ-001" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.costing.project_form.project_name') }}</label>
                            <input type="text" name="name" class="form-control" placeholder="Factory expansion" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.costing.project_form.customer_vendor') }}</label>
                            <select name="contact_id" class="form-select" data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.costing.project_form.select_contact') }}</option>
                                @foreach ($contactOptions as $contactId => $contactLabel)
                                    <option value="{{ $contactId }}">{{ $contactLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.cost_center') }}</label>
                            <select name="cost_center_id" class="form-select" data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_cost_center') }}</option>
                                @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                    <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.costing.project_form.start_date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.budget') }}</label>
                                <input type="number" step="0.0001" min="0" name="budget_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.costing.project_form.save') }}</button>
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
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.costing.department_register.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.costing.department_register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-costing-department-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-costing-department-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.department') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.costing.department_register.table.cost_centers') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.costing.department_register.table.actual') }}</th>
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
                                        <td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.costing.department_register.empty') }}</td>
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
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.costing.cost_center_register.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.costing.cost_center_register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-costing-cost-center-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-costing-cost-center-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.cost_center') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.costing.cost_center_register.table.projects') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.costing.cost_center_register.table.actual') }}</th>
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
                                        <td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.costing.cost_center_register.empty') }}</td>
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
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.costing.project_register.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.costing.project_register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-costing-project-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-costing-project-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.project') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.budget') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.costing.project_register.table.actual') }}</th>
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
                                        <td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.costing.project_register.empty') }}</td>
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

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const departmentTable = window.VasWorkspace?.initLocalDataTable('#vas-costing-department-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (departmentTable) {
                $('#vas-costing-department-search').on('keyup', function () {
                    departmentTable.search(this.value).draw();
                });
            }

            const costCenterTable = window.VasWorkspace?.initLocalDataTable('#vas-costing-cost-center-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (costCenterTable) {
                $('#vas-costing-cost-center-search').on('keyup', function () {
                    costCenterTable.search(this.value).draw();
                });
            }

            const projectTable = window.VasWorkspace?.initLocalDataTable('#vas-costing-project-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (projectTable) {
                $('#vas-costing-project-search').on('keyup', function () {
                    projectTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
