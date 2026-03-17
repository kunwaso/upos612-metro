@extends('projectx::layouts.main')

@section('title', __('essentials::lang.add_pay_component'))

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">@lang('essentials::lang.add_pay_component')</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.allowance-deduction.store') }}">
            @csrf
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('lang_v1.description')</label>
                    <input type="text" name="description" class="form-control form-control-solid" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('lang_v1.type')</label>
                    <select name="type" class="form-select form-select-solid" data-control="select2" data-hide-search="true" required>
                        <option value="allowance">@lang('essentials::lang.allowance')</option>
                        <option value="deduction">@lang('essentials::lang.deduction')</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.amount_type')</label>
                    <select name="amount_type" class="form-select form-select-solid" data-control="select2" data-hide-search="true" required>
                        <option value="fixed">@lang('lang_v1.fixed')</option>
                        <option value="percent">@lang('lang_v1.percentage')</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('sale.amount')</label>
                    <input type="text" name="amount" class="form-control form-control-solid input_number" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('essentials::lang.applicable_date')</label>
                    <input type="text" name="applicable_date" class="form-control form-control-solid projectx-flatpickr-date">
                </div>
                <div class="col-md-12">
                    <label class="form-label">@lang('essentials::lang.employee')</label>
                    <select name="employees[]" class="form-select form-select-solid" data-control="select2" multiple>
                        @foreach($users as $user_id => $user_name)
                            <option value="{{ $user_id }}">{{ $user_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-7">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <a href="{{ route('projectx.essentials.allowance-deduction.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
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
