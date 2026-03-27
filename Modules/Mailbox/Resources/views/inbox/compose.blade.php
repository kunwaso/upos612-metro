@extends('layouts.app')

@section('title', __('mailbox::lang.compose'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('mailbox::lang.compose') }}
                </h1>
                <div class="text-muted fw-semibold fs-7">Send a message from your connected mailbox</div>
            </div>
            <a href="{{ route('mailbox.index') }}" class="btn btn-sm btn-light">Back to inbox</a>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @include('mailbox::partials.status')

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between py-3">
                    <h2 class="card-title m-0">{{ __('mailbox::lang.compose') }}</h2>
                </div>
                <div class="card-body p-0">
                    <form method="POST" action="{{ route('mailbox.compose.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="d-block">
                            <div class="d-flex align-items-center border-bottom px-8 min-h-50px">
                                <div class="text-gray-900 fw-bold w-75px">From:</div>
                                <select name="account_id" class="form-select form-select-transparent border-0">
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ (int) old('account_id', $selectedAccountId) === (int) $account->id ? 'selected' : '' }}>
                                            {{ $account->display_name ?: $account->email_address }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex align-items-center border-bottom px-8 min-h-50px">
                                <div class="text-gray-900 fw-bold w-75px">To:</div>
                                <input type="text" class="form-control form-control-transparent border-0" name="to" value="{{ old('to', $replyMessage ? collect($replyMessage->reply_to_json)->pluck('email')->implode(',') ?: collect($replyMessage->from_json)->pluck('email')->implode(',') : '') }}" placeholder="person@example.com, other@example.com" />
                            </div>
                            <div class="d-flex align-items-center border-bottom px-8 min-h-50px">
                                <div class="text-gray-900 fw-bold w-75px">Cc:</div>
                                <input type="text" class="form-control form-control-transparent border-0" name="cc" value="{{ old('cc') }}" placeholder="Optional" />
                            </div>
                            <div class="d-flex align-items-center border-bottom px-8 min-h-50px">
                                <div class="text-gray-900 fw-bold w-75px">Bcc:</div>
                                <input type="text" class="form-control form-control-transparent border-0" name="bcc" value="{{ old('bcc') }}" placeholder="Optional" />
                            </div>
                            <div class="border-bottom">
                                <input class="form-control form-control-transparent border-0 px-8 min-h-45px" name="subject" placeholder="Subject" value="{{ old('subject', $replyMessage ? 'Re: ' . ($replyMessage->subject ?: '') : '') }}" />
                            </div>
                            <div class="px-8 py-6">
                                @if($replyMessage)
                                    <input type="hidden" name="reply_message_id" value="{{ $replyMessage->id }}">
                                @endif
                                <textarea name="body_html" rows="12" class="form-control form-control-solid" placeholder="Write your message">{{ old('body_html') }}</textarea>
                            </div>
                            <div class="px-8 py-4 border-top">
                                <label class="form-label fw-bold">Attachments</label>
                                <input type="file" name="attachments[]" multiple class="form-control form-control-solid">
                            </div>
                        </div>
                        <div class="d-flex flex-stack flex-wrap gap-2 py-5 ps-8 pe-5 border-top">
                            <div class="d-flex align-items-center me-3">
                                <button type="submit" class="btn btn-primary fs-bold px-6">Send</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
    <link href="{{ asset('modules/mailbox/css/mailbox.css') }}" rel="stylesheet" type="text/css" />
@endsection
