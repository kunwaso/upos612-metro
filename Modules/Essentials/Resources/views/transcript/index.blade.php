@extends('layouts.app')

@section('title', __('essentials::lang.voice_transcripts'))

@section('content')
@include('essentials::layouts.nav_essentials')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('essentials::lang.voice_transcripts')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('essentials::lang.manage_transcripts')
        </small>
    </h1>
</section>

<section class="content">

    {{-- Input card --}}
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('essentials::lang.new_transcript')</h3>
        </div>
        <div class="box-body">

            {{-- Tabs --}}
            <ul class="nav nav-pills nav-justified" id="transcript-tabs" role="tablist">
                <li class="active">
                    <a href="#tab-upload" data-toggle="tab" role="tab">
                        <i class="fa fa-upload"></i> @lang('essentials::lang.upload_audio')
                    </a>
                </li>
                <li>
                    <a href="#tab-live" data-toggle="tab" role="tab">
                        <i class="fa fa-microphone"></i> @lang('essentials::lang.record_live')
                    </a>
                </li>
            </ul>

            <div class="tab-content" style="margin-top:20px;">

                {{-- Tab 1: Upload File --}}
                <div class="tab-pane active" id="tab-upload">
                    <form id="upload-transcript-form" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('essentials::lang.transcript_title') <small class="text-muted">(@lang('messages.optional'))</small></label>
                                    <input type="text" name="title" class="form-control" placeholder="@lang('essentials::lang.transcript_title_placeholder')">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>@lang('essentials::lang.upload_audio') <span class="text-danger">*</span></label>
                                    <input type="file" name="audio" accept="audio/*,.mp3,.wav,.m4a,.webm,.ogg,.flac" class="form-control" id="audio-file-input">
                                    <p class="help-block">@lang('essentials::lang.audio_format_help')</p>
                                </div>
                            </div>
                            <div class="col-md-3" style="padding-top:25px;">
                                <button type="submit" class="btn btn-primary btn-block" id="btn-upload-transcribe">
                                    <i class="fa fa-magic"></i> @lang('essentials::lang.transcribe')
                                    <span class="spinner-upload" style="display:none;"><i class="fa fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Tab 2: Live Record --}}
                <div class="tab-pane" id="tab-live">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>@lang('essentials::lang.transcript_title') <small class="text-muted">(@lang('messages.optional'))</small></label>
                                <input type="text" id="live-title-input" class="form-control" placeholder="@lang('essentials::lang.transcript_title_placeholder')">
                            </div>
                        </div>
                        <div class="col-md-8" style="padding-top:25px;">
                            <button type="button" class="btn btn-danger" id="btn-start-record">
                                <i class="fa fa-microphone"></i> @lang('essentials::lang.start_recording')
                            </button>
                            <button type="button" class="btn btn-default" id="btn-stop-record" disabled>
                                <i class="fa fa-stop"></i> @lang('essentials::lang.stop_transcribe')
                            </button>
                            <span id="recording-indicator" style="display:none; margin-left:10px;">
                                <span class="label label-danger">
                                    <i class="fa fa-circle fa-blink"></i> @lang('essentials::lang.recording')
                                </span>
                                <span id="recording-timer" class="text-muted" style="margin-left:6px;">0s</span>
                            </span>
                        </div>
                    </div>
                    <div class="row" id="live-spinner-row" style="display:none; margin-top:10px;">
                        <div class="col-xs-12">
                            <i class="fa fa-spinner fa-spin text-primary"></i>
                            <span class="text-muted"> @lang('essentials::lang.transcribing_please_wait')</span>
                        </div>
                    </div>
                </div>

            </div>{{-- /tab-content --}}

        </div>{{-- /box-body --}}
    </div>{{-- /box --}}

    {{-- Result preview --}}
    <div id="transcript-result-box" class="box box-solid box-success" style="display:none;">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('essentials::lang.transcript_result')</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-xs btn-default" id="btn-copy-transcript">
                    <i class="fa fa-copy"></i> @lang('essentials::lang.copy')
                </button>
            </div>
        </div>
        <div class="box-body">
            <p id="transcript-result-text" class="text-muted" style="white-space:pre-wrap;"></p>
        </div>
    </div>

    {{-- Listing card --}}
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('essentials::lang.all_transcripts')</h3>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="transcripts-table">
                    <thead>
                        <tr>
                            <th>@lang('essentials::lang.transcript_title')</th>
                            <th>@lang('essentials::lang.source')</th>
                            <th>@lang('essentials::lang.created_by')</th>
                            <th>@lang('essentials::lang.date')</th>
                            <th>@lang('essentials::lang.transcript_preview')</th>
                            <th>@lang('essentials::lang.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</section>

{{-- View full transcript modal --}}
<div class="modal fade" id="transcript-view-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="transcript-modal-title">@lang('essentials::lang.voice_transcripts')</h4>
            </div>
            <div class="modal-body">
                <p id="transcript-modal-text" style="white-space:pre-wrap; word-break:break-word;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {

    var storeUrl = '{{ route("essentials.transcripts.store") }}';
    var csrfToken = '{{ csrf_token() }}';

    // ─── DataTable ───────────────────────────────────────────────
    var table = $('#transcripts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("essentials.transcripts.index") }}',
        columns: [
            { data: 'title',      name: 'essentials_transcripts.title' },
            { data: 'source',     name: 'essentials_transcripts.source',     orderable: false },
            { data: 'user_name',  name: 'user_name',                          orderable: false },
            { data: 'created_at', name: 'essentials_transcripts.created_at' },
            { data: 'transcript', name: 'essentials_transcripts.transcript',  orderable: false },
            { data: 'action',     name: 'action',                             orderable: false },
        ],
    });

    // ─── Upload form ──────────────────────────────────────────────
    $('#upload-transcript-form').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $btn = $('#btn-upload-transcribe');
        $btn.prop('disabled', true).find('.spinner-upload').show();

        $.ajax({
            url: storeUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $btn.prop('disabled', false).find('.spinner-upload').hide();
                if (res.success) {
                    toastr.success(res.msg);
                    showResult(res.data.transcript_text);
                    table.ajax.reload();
                    $('#upload-transcript-form')[0].reset();
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).find('.spinner-upload').hide();
                var msg = xhr.responseJSON && xhr.responseJSON.msg
                    ? xhr.responseJSON.msg
                    : '{{ __("messages.something_went_wrong") }}';
                toastr.error(msg);
            },
        });
    });

    // ─── Live recording ───────────────────────────────────────────
    var mediaRecorder = null;
    var audioChunks  = [];
    var timerInterval = null;
    var elapsedSeconds = 0;

    $('#btn-start-record').on('click', function () {
        if (! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia) {
            toastr.error('{{ __("essentials::lang.mic_not_supported") }}');
            return;
        }
        navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
            audioChunks = [];
            elapsedSeconds = 0;
            $('#recording-timer').text('0s');
            timerInterval = setInterval(function () {
                elapsedSeconds++;
                $('#recording-timer').text(elapsedSeconds + 's');
            }, 1000);

            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.addEventListener('dataavailable', function (e) {
                audioChunks.push(e.data);
            });
            mediaRecorder.start();

            $('#btn-start-record').prop('disabled', true);
            $('#btn-stop-record').prop('disabled', false);
            $('#recording-indicator').show();
        }).catch(function () {
            toastr.error('{{ __("essentials::lang.mic_permission_denied") }}');
        });
    });

    $('#btn-stop-record').on('click', function () {
        if (! mediaRecorder) return;

        mediaRecorder.addEventListener('stop', function () {
            clearInterval(timerInterval);
            $('#recording-indicator').hide();
            $('#btn-start-record').prop('disabled', false);
            $('#btn-stop-record').prop('disabled', true);

            var mimeType = mediaRecorder.mimeType || 'audio/webm';
            var audioBlob = new Blob(audioChunks, { type: mimeType });
            var ext = mimeType.indexOf('ogg') !== -1 ? 'ogg' : 'webm';

            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('recorded_audio', audioBlob, 'recording.' + ext);
            var liveTitle = $('#live-title-input').val();
            if (liveTitle) formData.append('title', liveTitle);

            $('#live-spinner-row').show();

            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function (res) {
                    $('#live-spinner-row').hide();
                    if (res.success) {
                        toastr.success(res.msg);
                        showResult(res.data.transcript_text);
                        table.ajax.reload();
                        $('#live-title-input').val('');
                    } else {
                        toastr.error(res.msg);
                    }
                },
                error: function (xhr) {
                    $('#live-spinner-row').hide();
                    var msg = xhr.responseJSON && xhr.responseJSON.msg
                        ? xhr.responseJSON.msg
                        : '{{ __("messages.something_went_wrong") }}';
                    toastr.error(msg);
                },
            });

            mediaRecorder.stream.getTracks().forEach(function (t) { t.stop(); });
        });

        mediaRecorder.stop();
    });

    // ─── Show transcript result preview ──────────────────────────
    function showResult(text) {
        $('#transcript-result-text').text(text);
        $('#transcript-result-box').slideDown();
        $('html, body').animate({ scrollTop: $('#transcript-result-box').offset().top - 60 }, 400);
    }

    // ─── Copy transcript text ─────────────────────────────────────
    $('#btn-copy-transcript').on('click', function () {
        var text = $('#transcript-result-text').text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                toastr.success('{{ __("essentials::lang.copied") }}');
            });
        } else {
            var el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            toastr.success('{{ __("essentials::lang.copied") }}');
        }
    });

    // ─── View full transcript in modal ────────────────────────────
    $(document).on('click', '.btn-view-transcript', function () {
        var transcript = $(this).data('transcript');
        var title      = $(this).data('title');
        $('#transcript-modal-title').text(title);
        $('#transcript-modal-text').text(transcript);
        $('#transcript-view-modal').modal('show');
    });

    // ─── Delete transcript ─────────────────────────────────────────
    $(document).on('click', '.btn-delete-transcript', function () {
        var url = $(this).data('href');
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(function (confirmed) {
            if (confirmed) {
                $.ajax({
                    method: 'DELETE',
                    url: url,
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            toastr.success(res.msg);
                            table.ajax.reload();
                        } else {
                            toastr.error(res.msg);
                        }
                    },
                    error: function () {
                        toastr.error('{{ __("messages.something_went_wrong") }}');
                    },
                });
            }
        });
    });

});
</script>
@endsection
