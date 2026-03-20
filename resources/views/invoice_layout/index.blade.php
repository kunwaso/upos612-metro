@extends('layouts.app')
@section('title', __('barcode.barcodes'))

@section('content')

{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">
                @lang('barcode.barcodes')
                <span class="text-gray-500 fw-normal fs-6 ms-2">@lang('barcode.manage_your_barcodes')</span>
            </h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">@lang('barcode.barcodes')</li>
            </ul>
        </div>
    </div>
</div>
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">

	<div class="box">
        <div class="box-header">
        	<h3 class="box-title">@lang('barcode.all_your_barcode')</h3>
        	<div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{action([\App\Http\Controllers\BarcodeController::class, 'create'])}}">
				<i class="fa fa-plus"></i> @lang('barcode.add_new_setting')</a>
            </div>
        </div>
        <div class="box-body">
        	<table class="table table-bordered table-striped" id="barcode_table">
        		<thead>
        			<tr>
        				<th>@lang('barcode.setting_name')</th>
						<th>@lang('barcode.setting_description')</th>
						<th>Action</th>
        			</tr>
        		</thead>
        	</table>
        </div>
    </div>

    </div>
</div>
<!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){
        var barcode_table = $('#barcode_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader:false,
            buttons:[],
            ajax: '/barcodes',
            bPaginate: false,
            columnDefs: [ {
                "targets": 2,
                "orderable": false,
                "searchable": false
            } ]
        });
        $(document).on('click', 'button.delete_barcode_button', function(){
            var is_confirmed = confirm("{{ __('barcode.delete_confirm') }}");
            if(!is_confirmed){
                return;
            }

            var href = $(this).data('href');
            var data = $(this).serialize();

            $.ajax({
                method: "DELETE",
                url: href,
                dataType: "json",
                data: data,
                success: function(result){
                    if(result.success === true){
                        toastr.success(result.msg);
                        barcode_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
        $(document).on('click', 'button.set_default', function(){
            var href = $(this).data('href');
            var data = $(this).serialize();

            $.ajax({
                method: "get",
                url: href,
                dataType: "json",
                data: data,
                success: function(result){
                    if(result.success === true){
                        toastr.success(result.msg);
                        barcode_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    });
</script>
@endsection