@extends('layouts.app')

@section('title', __('vasaccounting::lang.chart_of_accounts'))

@section('content')
    @php
        $chartActions = '<div class="d-flex gap-3">'
            . '<a href="' . route('vasaccounting.setup.index') . '" class="btn btn-light-secondary btn-sm">' . __('vasaccounting::lang.setup') . '</a>'
            . '<form method="POST" action="' . route('vasaccounting.setup.bootstrap') . '">' . csrf_field() . '<button type="submit" class="btn btn-light-primary btn-sm">' . __('vasaccounting::lang.refresh_statutory_defaults') . '</button></form>'
            . '</div>';
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.chart_of_accounts'),
        'subtitle' => __('vasaccounting::lang.chart_subtitle'),
        'actions' => $chartActions,
    ])

    @if (!empty($autoBootstrapped))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>
                <div class="fw-bold">{{ __('vasaccounting::lang.auto_bootstrap_title') }}</div>
                <div class="text-muted">{{ __('vasaccounting::lang.auto_bootstrap_body') }}</div>
            </div>
        </div>
    @endif

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.statutory_accounts') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ data_get($bootstrapStatus, 'system_account_count', 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.manual_accounts') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ data_get($bootstrapStatus, 'manual_account_count', 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.bootstrap_status') }}</div>
                    <div class="text-gray-900 fw-bold fs-6">{{ data_get($bootstrapStatus, 'needs_bootstrap') ? __('vasaccounting::lang.bootstrap_needed') : __('vasaccounting::lang.bootstrap_ready') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Accounts</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Level</th>
                                    <th>Balance</th>
                                    <th>Origin</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accounts as $account)
                                    <tr>
                                        <td class="text-gray-900 fw-semibold">{{ $account->account_code }}</td>
                                        <td>{{ $account->account_name }}</td>
                                        <td>{{ $account->account_type }}</td>
                                        <td>{{ $account->level }}</td>
                                        <td>{{ ucfirst($account->normal_balance) }}</td>
                                        <td>
                                            <span class="badge {{ $account->is_system ? 'badge-light-primary' : 'badge-light-secondary' }}">
                                                {{ $account->is_system ? __('vasaccounting::lang.origin_statutory') : __('vasaccounting::lang.origin_manual') }}
                                            </span>
                                        </td>
                                        <td><span class="badge {{ $account->is_active ? 'badge-light-success' : 'badge-light-danger' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Add account</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.chart.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label required">Account code</label>
                            <input type="text" class="form-control form-control-solid" name="account_code">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">Account name</label>
                            <input type="text" class="form-control form-control-solid" name="account_name">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">Account type</label>
                            <input type="text" class="form-control form-control-solid" name="account_type">
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control form-control-solid" name="account_category">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">Normal balance</label>
                            <select class="form-select form-select-solid" name="normal_balance">
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Parent account</label>
                            <select class="form-select form-select-solid select2" name="parent_id" data-control="select2" data-placeholder="Select parent account">
                                <option value=""></option>
                                @foreach ($parentOptions as $parentAccount)
                                    <option value="{{ $parentAccount->id }}">
                                        {{ str_repeat('- ', max(0, ((int) $parentAccount->level) - 1)) }}{{ $parentAccount->account_code }} - {{ $parentAccount->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="text-muted fs-8 mt-2">{{ __('vasaccounting::lang.manual_extension_hint') }}</div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
