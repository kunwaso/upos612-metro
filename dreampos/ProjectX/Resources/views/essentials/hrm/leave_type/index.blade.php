@extends('projectx::layouts.main')

@section('title', __('essentials::lang.leave_type'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.leave_type')</h1>
    <a href="{{ route('projectx.essentials.hrm.leave-type.create') }}" class="btn btn-primary btn-sm">@lang('messages.add')</a>
</div>
<div class="card card-flush">
    <div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_leave_type_table">
            <thead>
                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                    <th>@lang('essentials::lang.leave_type')</th>
                    <th>@lang('essentials::lang.max_leave_count')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function(){
    var table = $('#projectx_leave_type_table').DataTable({
        processing:true,
        serverSide:true,
        ajax:'{{ route('projectx.essentials.hrm.leave-type.index') }}',
        columns:[
            {data:'leave_type', name:'leave_type'},
            {data:'max_leave_count', name:'max_leave_count'},
            {data:'action', name:'action', orderable:false, searchable:false}
        ]
    });

    $(document).on('click', '.projectx-delete-leave-type', function(e){
        e.preventDefault();
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }
        var id = $(this).data('id');
        $.ajax({
            method:'DELETE',
            url:@json(route('projectx.essentials.hrm.leave-type.destroy', ['leave_type' => '__ID__'])).replace('__ID__', id),
            data:{_token:@json(csrf_token())},
            success:function(){table.ajax.reload();}
        });
    });
})();
</script>
@endsection
