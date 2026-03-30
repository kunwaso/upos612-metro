@extends('layouts.app')

@section('title', $snapshot->snapshot_name ?: $snapshot->report_key)

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $snapshot->snapshot_name ?: $snapshot->report_key,
        'subtitle' => 'Ảnh chụp báo cáo được lưu lại từ hàng đợi báo cáo VAS.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Khóa báo cáo</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $vasAccountingUtil->reportKeyLabel((string) $snapshot->report_key) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Trạng thái</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $vasAccountingUtil->genericStatusLabel((string) $snapshot->status) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Thời điểm tạo</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Số dòng đã lưu</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ count($payload['rows'] ?? []) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($snapshot->status !== 'ready')
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-information-4 fs-2 text-warning mt-1"></i>
            <div>
                <div class="fw-bold">Ảnh chụp chưa sẵn sàng</div>
                <div class="text-muted">{{ $snapshot->error_message ?: 'Ảnh chụp này vẫn đang chờ hàng đợi xử lý.' }}</div>
            </div>
        </div>
    @else
        @if (!empty($payload['summary']))
            <div class="row g-5 g-xl-10 mb-8">
                @foreach ($payload['summary'] as $metric)
                    <div class="col-md-4 col-xl-3">
                        <div class="card card-flush h-100">
                            <div class="card-body">
                                <div class="text-muted fs-7 fw-semibold mb-2">{{ $metric['label'] }}</div>
                                <div class="text-gray-900 fw-bold fs-2">{{ $metric['value'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold m-0">Bộ dữ liệu ảnh chụp</h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('vasaccounting.reports.index') }}" class="btn btn-sm btn-light-primary">{{ $vasAccountingUtil->actionLabel('back_to_reports') }}</a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                @foreach (($payload['columns'] ?? []) as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($payload['rows'] ?? []) as $row)
                                <tr>
                                    @foreach ($row as $index => $cell)
                                        <td class="{{ $index === 0 ? 'fw-semibold text-gray-900' : 'text-gray-700' }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($payload['columns'] ?? []) ?: 1 }}" class="text-muted">Không có dòng dữ liệu nào được lưu trong ảnh chụp này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
