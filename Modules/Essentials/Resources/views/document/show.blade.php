<div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title text-center" id="exampleModalLabel">
            {{ $memo->name}}
        </h4>
        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12">
            {{ $memo->description}}
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="tw-dw-btn tw-dw-btn-secondary tw-text-white" data-dismiss="modal">Close</button>
      </div>
    </div>
</div>
