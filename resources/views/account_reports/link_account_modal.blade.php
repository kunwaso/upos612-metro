<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\AccountReportsController::class, 'postLinkAccount']), 'method' => 'post', 'id' => 'link_account_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title">@lang( 'account.link_account' ) - @lang( 'account.payment_ref_no' ): - {{$payment->payment_ref_no}}</h4>
    </div>

    <div class="modal-body">
        <div class="form-group">
            {!! Form::hidden('transaction_payment_id', $payment->id); !!}
            {!! Form::label('account_id', __( 'account.account' ) .":") !!}
            {!! Form::select('account_id', $accounts, $payment->account_id, ['class' => 'form-control', 'required']); !!}
        </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
