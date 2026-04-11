@extends('layouts.app')

@section('title', __('lang_v1.storage_slots'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <x-storagemanager::storage-toolbar
        :title="$storageToolbarTitle"
        :breadcrumbs="$storageToolbarBreadcrumbs"
        :map-location-id="$storageToolbarLocationId ?? null"
    />

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-5">
                    {{ session('status.msg') }}
                </div>
            @endif

            {{-- Location filter --}}
            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.slots.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-sm w-250px" onchange="this.form.submit()">
                            <option value="">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected($location_id == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <label class="fw-semibold text-gray-700 fs-6">@lang('lang_v1.zone')</label>
                        <select name="category_id" class="form-select form-select-sm w-250px" onchange="this.form.submit()">
                            <option value="">@lang('messages.all')</option>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" @selected(($category_id ?? 0) == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            {{-- Slots table --}}
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.storage_slots')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-gray-800 border-bottom border-gray-200">
                                    <th class="min-w-100px">@lang('lang_v1.storage_slot_code')</th>
                                    <th class="min-w-150px">@lang('business.location')</th>
                                    <th class="min-w-150px">@lang('lang_v1.zone')</th>
                                    <th class="min-w-150px">@lang('lang_v1.storage_area')</th>
                                    <th class="min-w-80px">@lang('lang_v1.row')</th>
                                    <th class="min-w-80px">@lang('lang_v1.position')</th>
                                    <th class="min-w-100px">@lang('lang_v1.max_capacity')</th>
                                    <th class="min-w-100px">@lang('lang_v1.status')</th>
                                    <th class="min-w-80px">@lang('lang_v1.occupied')</th>
                                    <th class="min-w-80px">@lang('lang_v1.available')</th>
                                    <th class="min-w-100px text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slots as $slot)
                                    @php
                                        $available = $slot->max_capacity > 0
                                            ? max(0, $slot->max_capacity - $slot->occupancy)
                                            : null;
                                        $isFull = $slot->max_capacity > 0 && $slot->occupancy >= $slot->max_capacity;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-primary fw-semibold fs-7">
                                                {{ $slot->slot_code ?: "{$slot->row}-{$slot->position}" }}
                                            </span>
                                        </td>
                                        <td class="text-gray-700">{{ optional($slot->location)->name ?? '—' }}</td>
                                        <td class="text-gray-700">{{ optional($slot->category)->name ?? '—' }}</td>
                                        <td class="text-gray-700">{{ optional($slot->area)->name ?? '—' }}</td>
                                        <td class="text-gray-700">{{ $slot->row }}</td>
                                        <td class="text-gray-700">{{ $slot->position }}</td>
                                        <td class="text-gray-700">{{ $slot->max_capacity ?: '∞' }}</td>
                                        <td>
                                            <span class="badge {{ $slot->status === 'active' ? 'badge-light-success' : 'badge-light-secondary' }}">
                                                {{ $slot->status ?? __('lang_v1.active') }}
                                            </span>
                                        </td>
                                        <td class="text-gray-700">{{ $slot->occupancy }}</td>
                                        <td>
                                            @if($isFull)
                                                <span class="badge badge-light-danger">@lang('lang_v1.slot_full')</span>
                                            @elseif($available !== null)
                                                <span class="badge badge-light-success">{{ $available }}</span>
                                            @else
                                                <span class="badge badge-light-success">@lang('lang_v1.slot_available')</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @can('storage_manager.manage')
                                            <a href="{{ route('storage-manager.slots.edit', $slot->id) }}" class="btn btn-sm btn-light-primary me-2">
                                                <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-sm btn-light-danger btn-delete-slot"
                                                data-slot-id="{{ $slot->id }}"
                                                data-url="{{ route('storage-manager.slots.destroy', $slot->id) }}">
                                                <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-6">@lang('lang_v1.no_slots_defined')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $slots->appends(request()->query())->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    $(document).on('click', '.btn-delete-slot', function () {
        var url = $(this).data('url');
        if (!confirm('{{ __("messages.sure") }}')) return;
        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.success) location.reload();
            }
        });
    });
});
</script>
@endsection
