<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\DocumentShareController::class, 'update'], [$id]), 'id' => 'share_document_form', 'method' => 'put']) !!}
    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title text-center" id="exampleModalLabel">
        @if(!empty($type))
          @lang('essentials::lang.share_memos')
        @else
          @lang('essentials::lang.share_document')
        @endif
      </h4>
    </div>
    <div class="modal-body">
      
        <input type="hidden" name="document_id" id="document_id" value="{{$id}}">
        <div class="form-group">
            {!! Form::label('user', __('essentials::lang.user').':') !!} <br>
            {!! Form::select('user[]', $users, $shared_user, ['class' => 'form-control select2', 'multiple' => 'multiple', 'style'=>"width: 50%; height:50%"]); !!}
        </div>
        <div class="form-group">
            {!! Form::label('role', __('essentials::lang.role').':') !!} <br>
            {!! Form::select('role[]', $roles, $shared_role, ['class' => 'form-control select2', 'multiple' => 'multiple', 'style'=>"width: 50%; height:50%"]); !!}
        </div>

    </div>
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right ladda-button doc-share-btn" data-style="expand-right">
          <span class="ladda-label">@lang('messages.update')</span>
      </button>
    </div>
  </div>
  {!! Form::close() !!}
</div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    __select2($('.select2'));
  })
</script>
