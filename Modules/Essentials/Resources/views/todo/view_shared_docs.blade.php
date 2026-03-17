<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
            <h4 class="modal-title" id="myModalLabel">
                @lang('essentials::lang.view_shared_docs')
            </h4>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <caption>
                        @lang('essentials::lang.spreadsheets')
                    </caption>
                    <thead>
                        <tr>
                            <th>
                                #
                            </th>
                            <th>
                                @lang('messages.name')
                            </th>
                            <th>
                                @lang('messages.action')
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($sheets) > 0)
                            @foreach($sheets as $spreadsheet)
                                <tr>
                                    <td>
                                        {{$loop->iteration}}
                                    </td>
                                    <td>
                                        {{$spreadsheet->sheet_name}}
                                    </td>
                                    <td>
                                        <a href="{{action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'show'], [$spreadsheet->sheet_id])}}" target="_blank" title="@lang('messages.view')" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent">
                                            <i class="fas fa-eye"></i>
                                            @lang('messages.view')
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3">
                                    <h4 class="text-center">
                                        @lang('essentials::lang.no_docs_found')
                                    </h4>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <caption>
                        @lang('lang_v1.documents')
                    </caption>
                    <thead>
                        <tr>
                            <th>
                                #
                            </th>
                            <th>@lang('lang_v1.file')</th>
                            <th>
                                @lang('messages.action')
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($todo->media->count())
                            @foreach($todo->media as $media)
                                <tr>
                                    <td>
                                        {{$loop->iteration}}
                                    </td>
                                    <td>{{$media->display_name}}</td>
                                    <td>
                                        <a href="{{$media->display_url}}" download class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent">
                                            @lang('lang_v1.download')
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3">
                                    <h4 class="text-center">
                                        @lang('essentials::lang.no_docs_found')
                                    </h4>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div>
  </div>
