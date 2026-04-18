@extends('layouts.app')

@section('title', __('essentials::lang.voice_transcripts'))

@section('content')
@include('essentials::layouts.nav_essentials')

<section class="content-header">
    <div class="mb-5">
        <h1 class="mb-1 text-gray-900 fw-bolder fs-2x">@lang('essentials::lang.voice_transcripts')</h1>
        <div class="text-muted fw-semibold fs-6">@lang('essentials::lang.manage_transcripts')</div>
    </div>
</section>

<section class="content">
    <div class="card card-flush mb-6">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 m-0">@lang('essentials::lang.new_transcript')</h3>
            </div>
        </div>
        <div class="card-body pt-0">
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-semibold mb-8" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary active" data-bs-toggle="tab" href="#kt_transcript_upload_tab" role="tab">
                        <i class="fa fa-upload me-2"></i>@lang('essentials::lang.upload_audio')
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary" data-bs-toggle="tab" href="#kt_transcript_live_tab" role="tab">
                        <i class="fa fa-microphone me-2"></i>@lang('essentials::lang.record_live')
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="kt_transcript_upload_tab" role="tabpanel">
                    <form id="upload-transcript-form" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">@lang('essentials::lang.transcript_title') <small class="text-muted">(@lang('messages.optional'))</small></label>
                                <input type="text" name="title" id="upload-title-input" class="form-control form-control-solid" placeholder="@lang('essentials::lang.transcript_title_placeholder')">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">@lang('essentials::lang.source_language')</label>
                                <select name="source_language" id="upload-source-language" class="form-select form-select-solid select2" data-control="select2" data-hide-search="false">
                                    @foreach($languageOptions as $languageKey => $languageLabel)
                                        <option value="{{ $languageKey }}" {{ $languageKey === $default_source_language ? 'selected' : '' }}>{{ $languageLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">@lang('essentials::lang.target_language')</label>
                                <select name="target_language" id="upload-target-language" class="form-select form-select-solid select2" data-control="select2" data-hide-search="false">
                                    @foreach($languageOptions as $languageKey => $languageLabel)
                                        <option value="{{ $languageKey }}" {{ $languageKey === $default_target_language ? 'selected' : '' }}>{{ $languageLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">@lang('essentials::lang.upload_audio') <span class="text-danger">*</span></label>
                                <input type="file" name="audio" id="audio-file-input" accept="audio/*,.mp3,.wav,.m4a,.webm,.ogg,.flac" class="form-control form-control-solid">
                                <div class="form-text">@lang('essentials::lang.audio_format_help')</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary" id="btn-upload-preview">
                                <span class="indicator-label"><i class="fa fa-language me-2"></i>@lang('essentials::lang.preview_translate')</span>
                                <span class="indicator-progress d-none">@lang('messages.please_wait') <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="kt_transcript_live_tab" role="tabpanel">
                    <div class="alert alert-warning d-none" id="live-unsupported-alert">
                        <i class="fa fa-exclamation-triangle me-2"></i>@lang('essentials::lang.live_transcript_unavailable')
                    </div>

                    <div class="row g-5">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">@lang('essentials::lang.transcript_title') <small class="text-muted">(@lang('messages.optional'))</small></label>
                            <input type="text" id="live-title-input" class="form-control form-control-solid" placeholder="@lang('essentials::lang.transcript_title_placeholder')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">@lang('essentials::lang.source_language')</label>
                            <select name="live_source_language" id="live-source-language" class="form-select form-select-solid select2" data-control="select2" data-hide-search="false">
                                @foreach($languageOptions as $languageKey => $languageLabel)
                                    <option value="{{ $languageKey }}" {{ $languageKey === $default_source_language ? 'selected' : '' }}>{{ $languageLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">@lang('essentials::lang.target_language')</label>
                            <select name="live_target_language" id="live-target-language" class="form-select form-select-solid select2" data-control="select2" data-hide-search="false">
                                @foreach($languageOptions as $languageKey => $languageLabel)
                                    <option value="{{ $languageKey }}" {{ $languageKey === $default_target_language ? 'selected' : '' }}>{{ $languageLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-danger" id="btn-start-record">
                                    <i class="fa fa-microphone me-2"></i>@lang('essentials::lang.start_recording')
                                </button>
                                <button type="button" class="btn btn-light-danger" id="btn-stop-record" disabled>
                                    <i class="fa fa-stop me-2"></i>@lang('essentials::lang.stop_transcribe')
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-4 d-none" id="recording-indicator">
                        <span class="badge badge-light-danger me-3"><i class="fa fa-circle me-1"></i>@lang('essentials::lang.recording_in_progress')</span>
                        <span id="recording-timer" class="text-muted fw-semibold">0s</span>
                    </div>

                    <div class="d-flex align-items-center mt-3 d-none" id="live-spinner-row">
                        <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                        <span class="text-muted fw-semibold">@lang('essentials::lang.transcribing_please_wait')</span>
                    </div>

                    <div class="row g-5 mt-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">@lang('essentials::lang.live_transcript')</label>
                            <textarea id="live-transcript-text" class="form-control form-control-solid" rows="8" readonly placeholder="@lang('essentials::lang.live_transcript')"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">@lang('essentials::lang.live_translation')</label>
                            <textarea id="live-translation-text" class="form-control form-control-solid" rows="8" readonly placeholder="@lang('essentials::lang.live_translation')"></textarea>
                            <div id="live-translation-status" class="form-text mt-2 d-none" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="transcript-result-card" class="card card-flush mb-6 d-none">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 m-0">@lang('essentials::lang.transcript_result')</h3>
                <span class="badge badge-light-primary ms-3" id="result-language-pair">-</span>
            </div>
            <div class="card-toolbar d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light-primary" id="btn-copy-transcript">
                    <i class="fa fa-copy me-1"></i>@lang('essentials::lang.copy') @lang('essentials::lang.live_transcript')
                </button>
                <button type="button" class="btn btn-sm btn-light-primary" id="btn-copy-translation">
                    <i class="fa fa-copy me-1"></i>@lang('essentials::lang.copy') @lang('essentials::lang.live_translation')
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">@lang('essentials::lang.transcript_result')</label>
                    <textarea id="transcript-result-text" class="form-control form-control-solid" rows="8" readonly></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">@lang('essentials::lang.translated_text')</label>
                    <textarea id="translation-result-text" class="form-control form-control-solid" rows="8" readonly></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 m-0">@lang('essentials::lang.all_transcripts')</h3>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="transcripts-table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>@lang('essentials::lang.transcript_title')</th>
                            <th>@lang('essentials::lang.source')</th>
                            <th>@lang('essentials::lang.language_pair')</th>
                            <th>@lang('essentials::lang.created_by')</th>
                            <th>@lang('essentials::lang.date')</th>
                            <th>@lang('essentials::lang.transcript_preview')</th>
                            <th>@lang('essentials::lang.translated_preview')</th>
                            <th>@lang('essentials::lang.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="transcript-view-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="transcript-modal-title">@lang('essentials::lang.voice_transcripts')</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="mb-5">
                    <span class="badge badge-light-primary" id="transcript-modal-language-pair">-</span>
                </div>
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">@lang('essentials::lang.transcript_result')</label>
                        <textarea id="transcript-modal-text" class="form-control form-control-solid" rows="10" readonly></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">@lang('essentials::lang.translated_text')</label>
                        <textarea id="transcript-modal-translated-text" class="form-control form-control-solid" rows="10" readonly></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="save-transcript-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">@lang('essentials::lang.save_confirm_title')</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" id="btn-close-save-modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-5">
                    <i class="fa fa-info-circle me-2"></i>@lang('essentials::lang.save_confirm_description')
                </div>
                <div class="mb-4 d-flex flex-wrap gap-3">
                    <span class="badge badge-light-primary" id="save-modal-language-pair">-</span>
                    <span class="badge badge-light-info" id="save-modal-source">-</span>
                </div>
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">@lang('essentials::lang.transcript_result')</label>
                        <textarea id="save-modal-transcript-text" class="form-control form-control-solid" rows="10" readonly></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">@lang('essentials::lang.translated_text')</label>
                        <textarea id="save-modal-translation-text" class="form-control form-control-solid" rows="10" readonly></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="btn-discard-transcript">@lang('essentials::lang.discard_transcript')</button>
                <button type="button" class="btn btn-primary" id="btn-save-transcript">
                    <span class="indicator-label">@lang('essentials::lang.save_transcript')</span>
                    <span class="indicator-progress d-none">@lang('messages.please_wait') <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    var storeUrl = '{{ route("essentials.transcripts.store") }}';
    var previewUrl = '{{ route("essentials.transcripts.preview") }}';
    var translateUrl = '{{ route("essentials.transcripts.translate") }}';
    var csrfToken = '{{ csrf_token() }}';

    var languageLabels = @json($languageOptions);
    var speechLocales = @json($speech_locales);

    var saveModal = null;
    var viewModal = null;
    if (window.bootstrap) {
        saveModal = new bootstrap.Modal(document.getElementById('save-transcript-modal'));
        viewModal = new bootstrap.Modal(document.getElementById('transcript-view-modal'));
    }

    var state = {
        mediaRecorder: null,
        mediaStream: null,
        audioChunks: [],
        recognition: null,
        recognitionShouldRestart: false,
        isRecording: false,
        timerInterval: null,
        elapsedSeconds: 0,
        liveTranscriptLines: [],
        liveTranscriptFinal: '',
        liveTranscriptInterim: '',
        liveTranslationLines: [],
        liveTranslationFinal: '',
        liveTranslationInterim: '',
        translationQueue: [],
        translationDebounceTimer: null,
        translationRequestInFlight: false,
        translationPreviewQueueText: '',
        translationPreviewDebounceTimer: null,
        translationPreviewRequestInFlight: false,
        lastTranslationError: '',
        pending: null,
        saveInProgress: false
    };

    function initLanguageSelectors() {
        var $selectors = $('#upload-source-language, #upload-target-language, #live-source-language, #live-target-language');
        if (!$selectors.length || !$.fn || !$.fn.select2) {
            return;
        }

        $selectors.each(function () {
            var $select = $(this);
            if (!$select.hasClass('select2')) {
                $select.addClass('select2');
            }

            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            if (typeof __select2 === 'function') {
                __select2($select);
            } else {
                $select.select2();
            }
        });
    }

    initLanguageSelectors();

    var table = $('#transcripts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("essentials.transcripts.index") }}',
        columns: [
            { data: 'title', name: 'essentials_transcripts.title' },
            { data: 'source', name: 'essentials_transcripts.source', orderable: false, searchable: false },
            { data: 'language_pair', name: 'language_pair', orderable: false },
            { data: 'user_name', name: 'user_name', orderable: false },
            { data: 'created_at', name: 'essentials_transcripts.created_at' },
            { data: 'transcript', name: 'essentials_transcripts.transcript', orderable: false },
            { data: 'translated_preview', name: 'essentials_transcripts.translated_text', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    function extractAjaxErrorMessage(xhr, fallbackMessage) {
        var fallback = fallbackMessage || '{{ __("messages.something_went_wrong") }}';
        var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;

        if (response && typeof response.msg === 'string' && response.msg.trim()) {
            return response.msg;
        }

        if (response && typeof response.message === 'string' && response.message.trim()) {
            return response.message;
        }

        if (response && response.errors && typeof response.errors === 'object') {
            var fields = Object.keys(response.errors);
            for (var i = 0; i < fields.length; i++) {
                var fieldErrors = response.errors[fields[i]];
                if (Array.isArray(fieldErrors) && fieldErrors.length && typeof fieldErrors[0] === 'string') {
                    return fieldErrors[0];
                }

                if (typeof fieldErrors === 'string' && fieldErrors.trim()) {
                    return fieldErrors;
                }
            }
        }

        return fallback;
    }

    function getLanguagePairLabel(sourceLanguage, targetLanguage) {
        var source = languageLabels[sourceLanguage] || (sourceLanguage || '').toUpperCase();
        var target = languageLabels[targetLanguage] || (targetLanguage || '').toUpperCase();
        return source + ' -> ' + target;
    }

    function getSpeechLocale(language) {
        return speechLocales[language] || 'en-US';
    }

    function setButtonLoading($button, isLoading) {
        var $label = $button.find('.indicator-label');
        var $progress = $button.find('.indicator-progress');
        if (isLoading) {
            $button.prop('disabled', true);
            $label.addClass('d-none');
            $progress.removeClass('d-none');
        } else {
            $button.prop('disabled', false);
            $label.removeClass('d-none');
            $progress.addClass('d-none');
        }
    }

    function syncResultCard(payload) {
        $('#result-language-pair').text(getLanguagePairLabel(payload.source_language, payload.target_language));
        $('#transcript-result-text').val(payload.transcript_text || '');
        $('#translation-result-text').val(payload.translated_text || '');
        $('#transcript-result-card').removeClass('d-none');
    }

    function showResult(payload) {
        syncResultCard(payload);
        $('html, body').animate({ scrollTop: $('#transcript-result-card').offset().top - 70 }, 300);
    }

    function hideResult() {
        $('#result-language-pair').text('-');
        $('#transcript-result-text').val('');
        $('#translation-result-text').val('');
        $('#transcript-result-card').addClass('d-none');
    }

    function copyToClipboard(text) {
        if (!text) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                toastr.success('{{ __("essentials::lang.copied") }}');
            });
            return;
        }

        var el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        toastr.success('{{ __("essentials::lang.copied") }}');
    }

    function openSaveModal() {
        if (!state.pending) {
            return;
        }

        syncSaveModal();

        if (saveModal) {
            saveModal.show();
        } else {
            $('#save-transcript-modal').modal('show');
        }
    }

    function syncSaveModal() {
        if (!state.pending) {
            return;
        }

        $('#save-modal-language-pair').text(getLanguagePairLabel(state.pending.source_language, state.pending.target_language));
        $('#save-modal-source').text(state.pending.source === 'live' ? '{{ __("essentials::lang.record_live") }}' : '{{ __("essentials::lang.upload_audio") }}');
        $('#save-modal-transcript-text').val(state.pending.transcript_text || '');
        $('#save-modal-translation-text').val(state.pending.translated_text || '');
    }

    function closeSaveModal() {
        if (saveModal) {
            saveModal.hide();
        } else {
            $('#save-transcript-modal').modal('hide');
        }
    }

    function resetLivePanel() {
        state.liveTranscriptLines = [];
        state.liveTranscriptFinal = '';
        state.liveTranscriptInterim = '';
        state.liveTranslationLines = [];
        state.liveTranslationFinal = '';
        state.liveTranslationInterim = '';
        state.translationQueue = [];
        state.translationPreviewQueueText = '';
        state.translationRequestInFlight = false;
        state.translationPreviewRequestInFlight = false;
        state.lastTranslationError = '';
        clearTimeout(state.translationDebounceTimer);
        clearTimeout(state.translationPreviewDebounceTimer);
        $('#live-transcript-text').val('');
        $('#live-translation-text').val('');
        clearLiveTranslationStatus();
    }

    function stopMediaTracks() {
        if (state.mediaStream) {
            state.mediaStream.getTracks().forEach(function (track) { track.stop(); });
            state.mediaStream = null;
        }
    }

    function stopRecognition() {
        state.recognitionShouldRestart = false;
        if (state.recognition) {
            try {
                state.recognition.stop();
            } catch (e) {
                // no-op
            }
            state.recognition = null;
        }
    }

    function stopRecordingTimer() {
        clearInterval(state.timerInterval);
        state.timerInterval = null;
        state.elapsedSeconds = 0;
        $('#recording-timer').text('0s');
    }

    function getCurrentLiveTranscriptText() {
        return ($('#live-transcript-text').val() || '').trim();
    }

    function getCurrentLiveTranslationText() {
        return ($('#live-translation-text').val() || '').trim();
    }

    function getLineCollectionText(lines) {
        return (lines || [])
            .map(function (line) {
                return (line || '').trim();
            })
            .filter(function (line) {
                return line !== '';
            })
            .join("\n");
    }

    function syncLiveTextCaches() {
        state.liveTranscriptFinal = getLineCollectionText(state.liveTranscriptLines);
        state.liveTranslationFinal = getLineCollectionText(state.liveTranslationLines);
    }

    function buildLivePanelText(lines, interimText) {
        var finalPart = getLineCollectionText(lines);
        var interimPart = (interimText || '').trim();

        if (finalPart && interimPart) {
            return finalPart + "\n" + interimPart;
        }

        return finalPart || interimPart;
    }

    function buildLivePending(blob, mimeType) {
        return {
            source: 'live',
            title: ($('#live-title-input').val() || '').trim(),
            source_language: $('#live-source-language').val(),
            target_language: $('#live-target-language').val(),
            transcript_text: getCurrentLiveTranscriptText(),
            translated_text: getCurrentLiveTranslationText(),
            blob: blob || null,
            blobMimeType: mimeType || ''
        };
    }

    function syncLivePendingFromPanels() {
        if (!state.pending || state.pending.source !== 'live') {
            return;
        }

        state.pending.title = ($('#live-title-input').val() || '').trim();
        state.pending.source_language = $('#live-source-language').val();
        state.pending.target_language = $('#live-target-language').val();
        state.pending.transcript_text = getCurrentLiveTranscriptText();
        state.pending.translated_text = getCurrentLiveTranslationText();

        syncResultCard(state.pending);
        syncSaveModal();
    }

    function clearTranslationError() {
        state.lastTranslationError = '';
        clearLiveTranslationStatus();
    }

    function showTranslationError(message) {
        var msg = (message || '').trim();
        if (!msg) {
            return;
        }

        setLiveTranslationStatus(msg, 'error');
        if (state.lastTranslationError === msg) {
            return;
        }

        state.lastTranslationError = msg;
        toastr.error(msg);
    }

    function clearLiveTranslationStatus() {
        $('#live-translation-status')
            .addClass('d-none')
            .removeClass('text-danger text-muted')
            .text('');
    }

    function setLiveTranslationStatus(message, tone) {
        var msg = (message || '').trim();
        if (!msg) {
            clearLiveTranslationStatus();
            return;
        }

        var statusClass = tone === 'error' ? 'text-danger' : 'text-muted';
        $('#live-translation-status')
            .removeClass('d-none text-danger text-muted')
            .addClass(statusClass)
            .text(msg);
    }

    function updateLiveTranscript(interimText) {
        state.liveTranscriptInterim = (interimText || '').trim();

        if (!state.liveTranscriptInterim) {
            state.liveTranslationInterim = '';
            state.translationPreviewQueueText = '';
            clearTimeout(state.translationPreviewDebounceTimer);
        }

        $('#live-transcript-text').val(buildLivePanelText(state.liveTranscriptLines, state.liveTranscriptInterim));
        syncLivePendingFromPanels();
    }

    function updateLiveTranslation() {
        $('#live-translation-text').val(buildLivePanelText(state.liveTranslationLines, state.liveTranslationInterim));
        syncLivePendingFromPanels();
    }

    function flushLiveTranslationPreview() {
        if (!state.translationPreviewQueueText || state.translationPreviewRequestInFlight) {
            return;
        }

        var sourceLanguage = $('#live-source-language').val();
        var targetLanguage = $('#live-target-language').val();
        var previewText = (state.translationPreviewQueueText || '').trim();
        state.translationPreviewQueueText = '';

        if (!previewText) {
            state.liveTranslationInterim = '';
            updateLiveTranslation();
            return;
        }

        if (sourceLanguage === targetLanguage) {
            state.liveTranslationInterim = previewText;
            clearTranslationError();
            updateLiveTranslation();
            return;
        }

        state.translationPreviewRequestInFlight = true;
        $.ajax({
            url: translateUrl,
            method: 'POST',
            data: {
                _token: csrfToken,
                text: previewText,
                source_language: sourceLanguage,
                target_language: targetLanguage
            },
            success: function (res) {
                if (!res.success || !res.data) {
                    showTranslationError(res.msg || '{{ __("messages.something_went_wrong") }}');
                    return;
                }

                var translated = (res.data.translated_text || '').trim();
                if (!translated) {
                    showTranslationError('{{ __("essentials::lang.translation_empty_response") }}');
                    return;
                }

                if (state.liveTranscriptInterim !== previewText) {
                    return;
                }

                state.liveTranslationInterim = translated;
                clearTranslationError();
                updateLiveTranslation();
            },
            error: function (xhr) {
                if (state.liveTranscriptInterim !== previewText) {
                    return;
                }

                state.liveTranslationInterim = '';
                updateLiveTranslation();
                showTranslationError(extractAjaxErrorMessage(xhr, '{{ __("messages.something_went_wrong") }}'));
            },
            complete: function () {
                state.translationPreviewRequestInFlight = false;
                if (state.translationPreviewQueueText && state.translationPreviewQueueText !== previewText) {
                    flushLiveTranslationPreview();
                }
            }
        });
    }

    function queueLiveTranslationPreview(text) {
        var cleanText = (text || '').trim();
        state.translationPreviewQueueText = cleanText;
        clearTimeout(state.translationPreviewDebounceTimer);

        if (!cleanText) {
            state.liveTranslationInterim = '';
            updateLiveTranslation();
            return;
        }

        state.translationPreviewDebounceTimer = setTimeout(function () {
            flushLiveTranslationPreview();
        }, 450);
    }

    function flushLiveTranslationQueue() {
        if (!state.translationQueue.length || state.translationRequestInFlight) {
            return;
        }

        var sourceLanguage = $('#live-source-language').val();
        var targetLanguage = $('#live-target-language').val();
        var segmentEntry = state.translationQueue.shift();
        var segment = segmentEntry && segmentEntry.text ? segmentEntry.text.trim() : '';
        var segmentIndex = segmentEntry && typeof segmentEntry.index === 'number' ? segmentEntry.index : null;

        if (!segment || segmentIndex === null) {
            if (state.translationQueue.length) {
                flushLiveTranslationQueue();
            }
            return;
        }

        if (sourceLanguage === targetLanguage) {
            state.liveTranslationLines[segmentIndex] = segment;
            syncLiveTextCaches();
            clearTranslationError();
            updateLiveTranslation();

            if (state.translationQueue.length) {
                flushLiveTranslationQueue();
            }
            return;
        }

        state.translationRequestInFlight = true;
        $.ajax({
            url: translateUrl,
            method: 'POST',
            data: {
                _token: csrfToken,
                text: segment,
                source_language: sourceLanguage,
                target_language: targetLanguage
            },
            success: function (res) {
                if (!res.success || !res.data) {
                    showTranslationError(res.msg || '{{ __("messages.something_went_wrong") }}');
                    return;
                }

                var translated = (res.data.translated_text || '').trim();
                if (!translated) {
                    showTranslationError('{{ __("essentials::lang.translation_empty_response") }}');
                    return;
                }

                state.liveTranslationLines[segmentIndex] = translated;
                syncLiveTextCaches();
                clearTranslationError();
                updateLiveTranslation();
            },
            error: function (xhr) {
                state.liveTranslationLines[segmentIndex] = '{{ __("essentials::lang.live_translation_updating") }}';
                syncLiveTextCaches();
                updateLiveTranslation();
                showTranslationError(extractAjaxErrorMessage(xhr, '{{ __("messages.something_went_wrong") }}'));
            },
            complete: function () {
                state.translationRequestInFlight = false;
                if (state.translationQueue.length) {
                    flushLiveTranslationQueue();
                }
            }
        });
    }

    function queueLiveTranslation(text) {
        var cleanText = (text || '').trim();
        if (!cleanText) {
            return;
        }

        var lineIndex = state.liveTranscriptLines.length - 1;
        if (lineIndex < 0) {
            return;
        }

        state.translationQueue.push({
            index: lineIndex,
            text: cleanText
        });
        clearTimeout(state.translationDebounceTimer);
        state.translationDebounceTimer = setTimeout(function () {
            flushLiveTranslationQueue();
        }, 700);
    }

    function createSpeechRecognition(sourceLanguage) {
        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            return null;
        }

        var recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = getSpeechLocale(sourceLanguage);

        recognition.onresult = function (event) {
            var interim = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
                var currentText = event.results[i][0].transcript ? event.results[i][0].transcript.trim() : '';
                if (!currentText) {
                    continue;
                }

                if (event.results[i].isFinal) {
                    state.liveTranscriptLines.push(currentText);
                    state.liveTranslationLines.push('{{ __("essentials::lang.live_translation_updating") }}');
                    syncLiveTextCaches();
                    state.liveTranslationInterim = '';
                    queueLiveTranslation(currentText);
                } else {
                    interim += (interim ? ' ' : '') + currentText;
                }
            }

            updateLiveTranscript(interim);
            if (state.liveTranscriptInterim) {
                queueLiveTranslationPreview(state.liveTranscriptInterim);
            }
        };

        recognition.onerror = function () {
            // Keep recording even if browser live speech fails.
        };

        recognition.onend = function () {
            if (state.isRecording && state.recognitionShouldRestart) {
                try {
                    recognition.start();
                } catch (e) {
                    // no-op
                }
            }
        };

        return recognition;
    }

    function requestPreview(formData, onSuccess, onComplete, onError) {
        $.ajax({
            url: previewUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (!res.success || !res.data) {
                    var failMsg = res.msg || '{{ __("messages.something_went_wrong") }}';
                    toastr.error(failMsg);
                    if (typeof onError === 'function') {
                        onError(failMsg, res);
                    }
                    return;
                }
                onSuccess(res.data);
            },
            error: function (xhr) {
                var msg = extractAjaxErrorMessage(xhr, '{{ __("messages.something_went_wrong") }}');
                toastr.error(msg);
                if (typeof onError === 'function') {
                    onError(msg, xhr);
                }
            },
            complete: function () {
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }
        });
    }

    function discardPending(showToast) {
        var pendingSource = state.pending ? state.pending.source : null;
        state.pending = null;
        state.saveInProgress = false;
        hideResult();

        if (pendingSource === 'upload') {
            $('#upload-transcript-form')[0].reset();
        } else if (pendingSource === 'live') {
            $('#live-title-input').val('');
            resetLivePanel();
        }

        if (showToast) {
            toastr.info('{{ __("essentials::lang.recording_discarded") }}');
        }
    }

    function finishStopState() {
        state.isRecording = false;
        stopRecordingTimer();
        stopRecognition();
        stopMediaTracks();
        $('#recording-indicator').addClass('d-none');
        $('#btn-start-record').prop('disabled', false);
        $('#btn-stop-record').prop('disabled', true);
    }

    $('#upload-transcript-form').on('submit', function (e) {
        e.preventDefault();

        var fileInput = document.getElementById('audio-file-input');
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            toastr.error('{{ __("essentials::lang.audio_required") }}');
            return;
        }

        var sourceLanguage = $('#upload-source-language').val();
        var targetLanguage = $('#upload-target-language').val();
        var formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('audio', fileInput.files[0]);
        formData.append('source_language', sourceLanguage);
        formData.append('target_language', targetLanguage);

        setButtonLoading($('#btn-upload-preview'), true);
        requestPreview(formData, function (data) {
            state.pending = {
                source: 'upload',
                title: ($('#upload-title-input').val() || '').trim(),
                source_language: sourceLanguage,
                target_language: targetLanguage,
                transcript_text: data.transcript_text || '',
                translated_text: data.translated_text || ''
            };

            showResult(state.pending);
            openSaveModal();
        }, function () {
            setButtonLoading($('#btn-upload-preview'), false);
        });
    });

    $('#btn-start-record').on('click', function () {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            toastr.error('{{ __("essentials::lang.mic_not_supported") }}');
            return;
        }

        if (state.isRecording) {
            return;
        }

        var sourceLanguage = $('#live-source-language').val();
        resetLivePanel();
        $('#live-spinner-row').addClass('d-none');

        navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
            state.mediaStream = stream;
            state.audioChunks = [];
            state.isRecording = true;
            state.elapsedSeconds = 0;

            state.mediaRecorder = new MediaRecorder(stream);
            state.mediaRecorder.addEventListener('dataavailable', function (event) {
                if (event.data && event.data.size > 0) {
                    state.audioChunks.push(event.data);
                }
            });

            state.mediaRecorder.addEventListener('stop', function () {
                var mimeType = state.mediaRecorder && state.mediaRecorder.mimeType ? state.mediaRecorder.mimeType : 'audio/webm';
                var extension = mimeType.indexOf('ogg') !== -1 ? 'ogg' : 'webm';
                var blob = new Blob(state.audioChunks, { type: mimeType });
                var sourceLang = $('#live-source-language').val();
                var targetLang = $('#live-target-language').val();

                if (!blob.size) {
                    finishStopState();
                    toastr.error('{{ __("essentials::lang.audio_required") }}');
                    return;
                }

                $('#live-spinner-row').removeClass('d-none');
                var livePreviewState = buildLivePending(blob, mimeType);
                state.pending = null;
                showResult(livePreviewState);
                closeSaveModal();

                var formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('recorded_audio', blob, 'recording.' + extension);
                formData.append('source_language', sourceLang);
                formData.append('target_language', targetLang);

                requestPreview(formData, function (data) {
                    state.pending = $.extend({}, livePreviewState, {
                        source: 'live',
                        title: ($('#live-title-input').val() || '').trim(),
                        source_language: sourceLang,
                        target_language: targetLang,
                        transcript_text: (data.transcript_text || livePreviewState.transcript_text || '').trim(),
                        translated_text: (data.translated_text || livePreviewState.translated_text || '').trim(),
                        blob: blob,
                        blobMimeType: mimeType
                    });

                    showResult(state.pending);
                    syncSaveModal();
                    openSaveModal();
                }, function () {
                    $('#live-spinner-row').addClass('d-none');
                    finishStopState();
                }, function () {
                    state.pending = null;
                    closeSaveModal();
                });
            });

            state.mediaRecorder.start();
            $('#btn-start-record').prop('disabled', true);
            $('#btn-stop-record').prop('disabled', false);
            $('#recording-indicator').removeClass('d-none');

            state.timerInterval = setInterval(function () {
                state.elapsedSeconds++;
                $('#recording-timer').text(state.elapsedSeconds + 's');
            }, 1000);

            state.recognition = createSpeechRecognition(sourceLanguage);
            if (state.recognition) {
                $('#live-unsupported-alert').addClass('d-none');
                state.recognitionShouldRestart = true;
                try {
                    state.recognition.start();
                } catch (e) {
                    // keep stop-only mode
                }
            } else {
                $('#live-unsupported-alert').removeClass('d-none');
            }
        }).catch(function () {
            toastr.error('{{ __("essentials::lang.mic_permission_denied") }}');
        });
    });

    $('#btn-stop-record').on('click', function () {
        if (!state.mediaRecorder || !state.isRecording) {
            return;
        }

        clearTimeout(state.translationDebounceTimer);
        clearTimeout(state.translationPreviewDebounceTimer);
        flushLiveTranslationQueue();
        flushLiveTranslationPreview();

        state.isRecording = false;
        stopRecognition();
        $('#btn-stop-record').prop('disabled', true);

        try {
            if (state.mediaRecorder.state !== 'inactive') {
                state.mediaRecorder.stop();
            }
        } catch (e) {
            finishStopState();
            toastr.error('{{ __("messages.something_went_wrong") }}');
        }
    });

    $('#btn-save-transcript').on('click', function () {
        if (!state.pending || state.saveInProgress) {
            return;
        }

        var pending = state.pending;
        var formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('source', pending.source);
        formData.append('title', pending.title || '');
        formData.append('source_language', pending.source_language);
        formData.append('target_language', pending.target_language);
        formData.append('transcript_text', pending.transcript_text || '');
        formData.append('translated_text', pending.translated_text || '');

        if (pending.source === 'live') {
            if (!pending.blob) {
                toastr.error('{{ __("essentials::lang.audio_required") }}');
                return;
            }
            var extension = pending.blobMimeType && pending.blobMimeType.indexOf('ogg') !== -1 ? 'ogg' : 'webm';
            formData.append('recorded_audio', pending.blob, 'recording.' + extension);
        } else {
            var fileInput = document.getElementById('audio-file-input');
            if (!fileInput || !fileInput.files || !fileInput.files.length) {
                toastr.error('{{ __("essentials::lang.audio_required") }}');
                return;
            }
            formData.append('audio', fileInput.files[0]);
        }

        state.saveInProgress = true;
        setButtonLoading($('#btn-save-transcript'), true);

        $.ajax({
            url: storeUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (!res.success || !res.data) {
                    toastr.error(res.msg || '{{ __("messages.something_went_wrong") }}');
                    return;
                }

                toastr.success(res.msg || '{{ __("essentials::lang.recording_saved") }}');
                showResult(res.data);
                table.ajax.reload();

                if (pending.source === 'upload') {
                    $('#upload-transcript-form')[0].reset();
                } else {
                    $('#live-title-input').val('');
                    resetLivePanel();
                }

                state.pending = null;
                closeSaveModal();
            },
            error: function (xhr) {
                var msg = extractAjaxErrorMessage(xhr, '{{ __("messages.something_went_wrong") }}');
                toastr.error(msg);
            },
            complete: function () {
                state.saveInProgress = false;
                setButtonLoading($('#btn-save-transcript'), false);
            }
        });
    });

    $('#btn-discard-transcript, #btn-close-save-modal').on('click', function () {
        discardPending(true);
        closeSaveModal();
    });

    $('#btn-copy-transcript').on('click', function () {
        copyToClipboard($('#transcript-result-text').val());
    });

    $('#btn-copy-translation').on('click', function () {
        copyToClipboard($('#translation-result-text').val());
    });

    $(document).on('click', '.btn-view-transcript', function () {
        var transcript = $(this).data('transcript') || '';
        var translated = $(this).data('translated') || '';
        var title = $(this).data('title') || '{{ __("essentials::lang.voice_transcripts") }}';
        var languagePair = $(this).data('language-pair') || '-';

        $('#transcript-modal-title').text(title);
        $('#transcript-modal-language-pair').text(languagePair);
        $('#transcript-modal-text').val(transcript);
        $('#transcript-modal-translated-text').val(translated);

        if (viewModal) {
            viewModal.show();
        } else {
            $('#transcript-view-modal').modal('show');
        }
    });

    $(document).on('click', '.btn-delete-transcript', function () {
        var url = $(this).data('href');
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

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
                }
            });
        });
    });
});
</script>
@endsection
