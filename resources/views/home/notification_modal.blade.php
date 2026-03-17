<div class="modal-dialog" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title">@lang('lang_v1.system_notification')</h4>
    </div>
    <div class="modal-body">
      @foreach($notifications as $notification)
        @php
          $notification_data = $notification->data;
        @endphp
        <div class="row">
          <div class="col-md-12 mb-10"><h4 class="modal-title">{!! $notification_data['subject'] !!}</h4> <p class="text-muted">{{$notification->created_at->diffForHumans()}}</p></div>
          <div class="col-md-12">
            {!! $notification_data['msg'] !!}
          </div>
        </div>
        @if($loop->index > 0)
          <hr>
        @endif
      @endforeach
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
