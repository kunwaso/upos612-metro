@extends('layouts.app')
@section('title', __('cash_register.cash_register'))

@section('content')

{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">
                @lang('cash_register.cash_register')
                <span class="text-gray-500 fw-normal fs-6 ms-2">@lang('cash_register.manage_your_cash_register')</span>
            </h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">@lang('cash_register.cash_register')</li>
            </ul>
        </div>
    </div>
</div>
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">

	<div class="box">
        <div class="box-header">
        	<h3 class="box-title">@lang( 'cash_register.all_your_cash_register' )</h3>
        	<div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                	data-href="{{action([\App\Http\Controllers\CashRegisterController::class, 'create'])}}" 
                	data-container=".location_add_modal">
                	<i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
            </div>
        </div>
        <div class="box-body">
        	<table class="table table-bordered table-striped" id="cash_registers_table">
        		<thead>
        			<tr>
        				<th>@lang( 'invoice.name' )</th>
                        <th>@lang( 'messages.action' )</th>
        			</tr>
        		</thead>
        	</table>
        </div>
    </div>

    <div class="modal fade location_add_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade location_edit_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    </div>
</div>
<!-- /.content -->

@endsection
