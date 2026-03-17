@extends('projectx::layouts.main')

@section('title', $type === 'memos' ? __('essentials::lang.memos') : __('essentials::lang.document'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ $type === 'memos' ? __('essentials::lang.memos') : __('essentials::lang.document') }}</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('projectx.essentials.documents.index', ['type' => 'document']) }}" class="btn btn-sm {{ $type === 'document' ? 'btn-primary' : 'btn-light-primary' }}">@lang('essentials::lang.document')</a>
        <a href="{{ route('projectx.essentials.documents.index', ['type' => 'memos']) }}" class="btn btn-sm {{ $type === 'memos' ? 'btn-primary' : 'btn-light-primary' }}">@lang('essentials::lang.memos')</a>
    </div>
</div>

<div class="card card-flush mb-7">
    <div class="card-header pt-7">
        <h3 class="card-title fw-bold text-gray-900">@lang('essentials::lang.submit')</h3>
    </div>
    <div class="card-body pt-5">
        <form method="POST" action="{{ route('projectx.essentials.documents.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label required">@lang('essentials::lang.name')</label>
                    @if($type === 'memos')
                        <input type="text" name="name" class="form-control form-control-solid" value="{{ old('name') }}" required>
                    @else
                        <input type="file" name="name" class="form-control form-control-solid" required>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.description')</label>
                    <textarea name="description" rows="2" class="form-control form-control-solid">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-6">
                <button type="submit" class="btn btn-primary btn-sm">@lang('essentials::lang.submit')</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-flush">
    <div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_essentials_documents_table">
            <thead>
                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                    <th>@lang('essentials::lang.name')</th>
                    <th>@lang('essentials::lang.description')</th>
                    <th>@lang('essentials::lang.user')</th>
                    <th>@lang('essentials::lang.created_at')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    var table = $('#projectx_essentials_documents_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('projectx.essentials.documents.index') }}',
            data: function (d) {
                d.type = @json($type);
            }
        },
        columns: [
            {data: 'name', name: 'name'},
            {data: 'description', name: 'description'},
            {data: 'owner', name: 'owner'},
            {data: 'created_at', name: 'created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']]
    });

    $(document).on('click', '.projectx-delete-document', function (event) {
        event.preventDefault();
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        var id = $(this).data('id');
        var url = @json(route('projectx.essentials.documents.destroy', ['document' => '__ID__'])).replace('__ID__', id);

        $.ajax({
            method: 'DELETE',
            url: url,
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    table.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });
})();
</script>
@endsection
