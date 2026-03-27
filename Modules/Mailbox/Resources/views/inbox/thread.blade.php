@extends('layouts.app')

@section('title', $selectedMessage->subject ?: __('mailbox::lang.inbox'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack flex-wrap gap-4">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ $selectedMessage->subject ?: '(No subject)' }}
                </h1>
                <div class="text-muted fw-semibold fs-7">{{ __('mailbox::lang.mailbox') }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('mailbox.index', ['folder' => $filters['folder'], 'account_id' => $filters['account_id']]) }}" class="btn btn-sm btn-light">
                    Back to inbox
                </a>
                <a href="{{ route('mailbox.compose.create', ['reply_message_id' => $selectedMessage->id]) }}" class="btn btn-sm btn-primary">
                    Reply in composer
                </a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @include('mailbox::partials.status')

            <div class="d-flex flex-column flex-lg-row">
                @include('mailbox::partials.sidebar', ['accounts' => $accounts, 'counts' => $counts, 'filters' => $filters])

                <div class="flex-lg-row-fluid ms-lg-7 ms-xl-10">
                    <div class="card">
                        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                            <div class="d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('mailbox.messages.read', ['message' => $selectedMessage->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light">
                                        {{ $selectedMessage->is_read ? __('mailbox::lang.mark_as_unread') : __('mailbox::lang.mark_as_read') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('mailbox.messages.star', ['message' => $selectedMessage->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light">
                                        {{ $selectedMessage->is_starred ? 'Unstar' : 'Star' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('mailbox.messages.trash', ['message' => $selectedMessage->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light-danger">Move to trash</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            @foreach($threadMessages as $threadMessage)
                                @php
                                    $sender = collect($threadMessage->from_json)->first();
                                    $senderLabel = $sender['name'] ?? ($sender['email'] ?? 'Unknown');
                                @endphp
                                <div class="mailbox-thread-item {{ ! $loop->last ? 'border-bottom border-gray-200 pb-8 mb-8' : '' }}">
                                    <div class="d-flex flex-wrap gap-2 flex-stack">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50 me-4">
                                                <span class="symbol-label bg-light-primary text-primary">{{ strtoupper(substr($senderLabel, 0, 1)) }}</span>
                                            </div>
                                            <div class="pe-5">
                                                <div class="d-flex align-items-center flex-wrap gap-1">
                                                    <span class="fw-bold text-gray-900">{{ $senderLabel }}</span>
                                                    <span class="text-muted fw-bold">{{ $threadMessage->primary_timestamp ? $threadMessage->primary_timestamp->format('M d, Y H:i') : '' }}</span>
                                                </div>
                                                <div class="text-muted fw-semibold">
                                                    to {{ collect($threadMessage->to_json)->pluck('email')->implode(', ') ?: 'me' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($threadMessage->is_starred)
                                                <span class="badge badge-light-warning">Starred</span>
                                            @endif
                                            @if($threadMessage->has_attachments)
                                                <span class="badge badge-light-info">Attachments</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="py-5 mailbox-thread-body">
                                        @if(!empty($threadMessage->body_html))
                                            {!! $threadMessage->body_html !!}
                                        @else
                                            {!! nl2br(e($threadMessage->body_text ?: $threadMessage->snippet)) !!}
                                        @endif
                                    </div>

                                    @if($threadMessage->attachments->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach($threadMessage->attachments as $attachment)
                                                <a href="{{ route('mailbox.attachments.download', ['attachment' => $attachment->id]) }}" class="btn btn-sm btn-light-primary">
                                                    <i class="ki-duotone ki-paper-clip fs-5 me-1"></i>
                                                    {{ $attachment->filename }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            @can('mailbox.send')
                                <div class="separator my-8"></div>
                                <form method="POST" action="{{ route('mailbox.compose.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $selectedMessage->mailbox_account_id }}">
                                    <input type="hidden" name="reply_message_id" value="{{ $selectedMessage->id }}">
                                    <input type="hidden" name="to" value="{{ collect($selectedMessage->reply_to_json)->pluck('email')->implode(',') ?: collect($selectedMessage->from_json)->pluck('email')->implode(',') }}">
                                    <input type="hidden" name="subject" value="{{ \Illuminate\Support\Str::startsWith((string) $selectedMessage->subject, 'Re:') ? $selectedMessage->subject : 'Re: ' . ($selectedMessage->subject ?: '') }}">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Reply</label>
                                        <textarea name="body_html" rows="8" class="form-control form-control-solid" placeholder="Write your reply"></textarea>
                                    </div>
                                    <div class="row g-4 align-items-end">
                                        <div class="col-lg-9">
                                            <label class="form-label">Attachments</label>
                                            <input type="file" name="attachments[]" multiple class="form-control form-control-solid">
                                        </div>
                                        <div class="col-lg-3 d-grid">
                                            <button type="submit" class="btn btn-primary">Send reply</button>
                                        </div>
                                    </div>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
    <link href="{{ asset('modules/mailbox/css/mailbox.css') }}" rel="stylesheet" type="text/css" />
@endsection
