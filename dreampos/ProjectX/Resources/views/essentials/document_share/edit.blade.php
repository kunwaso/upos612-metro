@extends('projectx::layouts.main')

@section('title', __('essentials::lang.share_document'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.share_document')</h1>
    </div>
    <a href="{{ route('projectx.essentials.documents.index', ['type' => $type]) }}" class="btn btn-light-primary btn-sm">@lang('business.back')</a>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <form method="POST" action="{{ route('projectx.essentials.document-share.update', ['id' => $id]) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="document_id" value="{{ $id }}">
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.user')</label>
                    <select name="user[]" class="form-select form-select-solid" data-control="select2" multiple>
                        @foreach($users as $userId => $userLabel)
                            <option value="{{ $userId }}" {{ in_array((int) $userId, array_map('intval', $shared_user), true) ? 'selected' : '' }}>{{ $userLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.role')</label>
                    <select name="role[]" class="form-select form-select-solid" data-control="select2" multiple>
                        @foreach($roles as $roleId => $roleLabel)
                            <option value="{{ $roleId }}" {{ in_array((int) $roleId, array_map('intval', $shared_role), true) ? 'selected' : '' }}>{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-6">
                <button type="submit" class="btn btn-primary btn-sm">@lang('messages.update')</button>
            </div>
        </form>
    </div>
</div>
@endsection
