@extends('projectx::layouts.main')

@section('title', __('essentials::lang.add_holiday'))

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">@lang('essentials::lang.add_holiday')</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.holiday.store') }}">
            @csrf
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('lang_v1.name')</label>
                    <input type="text" name="name" class="form-control form-control-solid" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.start_date')</label>
                    <input type="text" name="start_date" class="form-control form-control-solid projectx-flatpickr-date" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.end_date')</label>
                    <input type="text" name="end_date" class="form-control form-control-solid projectx-flatpickr-date" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('business.business_location')</label>
                    <select name="location_id" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($locations as $location_id => $location_name)
                            <option value="{{ $location_id }}">{{ $location_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">@lang('brand.note')</label>
                    <textarea name="note" class="form-control form-control-solid" rows="3"></textarea>
                </div>
            </div>
            <div class="mt-7">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
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
