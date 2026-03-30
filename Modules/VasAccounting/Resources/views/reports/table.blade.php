@extends('layouts.app')

@section('title', $title)

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $title,
        'subtitle' => 'Dữ liệu báo cáo được tạo từ chứng từ VAS, bút toán nhật ký và các bảng kiểm soát doanh nghiệp.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Số dòng</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ count($rows) }}</div>
                    <div class="text-gray-600 fs-7 mt-1">Số dòng dữ liệu hiện tại sau khi áp dụng bộ lọc.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Số cột</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ count($columns) }}</div>
                    <div class="text-gray-600 fs-7 mt-1">Số cột hiển thị trong bảng kết quả báo cáo.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Khóa báo cáo</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ request()->route()?->getName() }}</div>
                    <div class="text-gray-600 fs-7 mt-1">Thanh điều hướng và khung giao diện được dùng thống nhất trên toàn bộ tuyến báo cáo.</div>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($summary))
        <div class="row g-5 g-xl-10 mb-8">
            @foreach ($summary as $metric)
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
                <h3 class="fw-bold m-0">Bộ dữ liệu hiển thị</h3>
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
                            @foreach ($columns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($row as $index => $cell)
                                    <td class="{{ $index === 0 ? 'fw-semibold text-gray-900' : 'text-gray-700' }}">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="text-muted">Không có dòng dữ liệu nào phù hợp với bộ lọc hiện tại.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
