@extends('projectx::layouts.main')

@section('title', __('essentials::lang.edit_holiday'))

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">@lang('essentials::lang.edit_holiday')</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.holiday.update', ['holiday' => $holiday->id]) }}">
            @csrf
            @method('PUT')
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('lang_v1.name')</label>
                    <input type="text" name="name" class="form-control form-control-solid" value="{{ $holiday->name }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.start_date')</label>
                    <input type="text" name="start_date" class="form-control form-control-solid projectx-flatpickr-date" value="{{ @format_date($holiday->start_date) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.end_date')</label>
                    <input type="text" name="end_date" class="form-control form-control-solid projectx-flatpickr-date" value="{{ @format_date($holiday->end_date) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('business.business_location')</label>
                    <select name="location_id" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($locations as $location_id => $location_name)
                            <option value="{{ $location_id }}" {{ (int) $holiday->location_id === (int) $location_id ? 'selected' : '' }}>
                                {{ $location_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">@lang('brand.note')</label>
                    <textarea name="note" class="form-control form-control-solid" rows="3">{{ $holiday->note }}</textarea>
                </div>
            </div>
            <div class="mt-7">
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                <a href="{{ route('projectx.essentials.hrm.holiday.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
$('.projectx-flatpickr-date').flatpickr({dateFormat: 'Y-m-d'});
</script>
@endsection
