<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">@lang('essentials::lang.import_attendance')</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.attendance.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-5 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">@lang('product.file_to_import')</label>
                    <input type="file" name="attendance" accept=".xls,.xlsx" class="form-control form-control-solid" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                </div>
            </div>
        </form>
    </div>
</div>
