<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
            <h4 class="modal-title">{!! $document_note->heading !!}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    {!! $document_note->description !!}
                </div>
            </div>
            @if(($document_note->media)->count() > 0)
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h4>@lang('lang_v1.documents')</h4>
                        @foreach($document_note->media as $media)
                            <a href="{{$media->display_url}}" download="{{$media->display_name}}">
                                <i class="fa fa-download"></i>
                                {{$media->display_name}}
                            </a><br>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <span class="pull-left">
                <i class="fas fa-pencil-alt"></i>
                {{$document_note->createdBy->user_full_name}}
                &nbsp;
                <i class="fa fa-calendar-check-o"></i>
                {{ format_date_value($document_note->created_at) }}
            </span>
            <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
